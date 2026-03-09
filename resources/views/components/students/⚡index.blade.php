<?php

use App\Models\Grade;
use App\Models\Student;
use Livewire\Component;
use Livewire\Attributes\Title;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new #[Title('Students')] class extends Component {
    use WithPagination;    

    public $grades = [];
    public ?Student $editingStudent = null;
    public $firstname, $lastname, $email, $dob, $contact_number, $address, $grade_id;
    public $search = '';

    // property to hold student being viewed
    public ?Student $viewingStudent = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function showStudent(Student $student)
    {
        $this->viewingStudent = $student;
        Flux::modal('view-student-modal')->show();
    }
    // Default value for pagination
    public $perPage = 10;

    public function mount()
    {
        if(Auth::user()->hasRole('admin')) {
            $this->grades = Grade::all();
        } else {
            $this->grades = Auth::user()->grades;
        }
    }
    
    #[Computed]
    function students() {
        return Student::where(function ($query) {
            $query->where('firstname', 'like', '%' . $this->search . '%')
                  ->orWhere('lastname', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
        })->latest()->paginate($this->perPage);
    }

    // Function that runs before the Add Student Modal is shown to reset the form state
    public function add()
    {
        $this->resetForm();
        Flux::modal('add-student-modal')->show();
    }

    // Function to populate the Edit Student Modal with the selected student's data
    public function edit(Student $student)
    {
        $this->editingStudent = $student;
        
        // Fill the form with the student's data
        $this->firstname = $student->firstname;
        $this->lastname = $student->lastname;
        $this->email = $student->email;
        $this->dob = $student->dob;
        $this->contact_number = $student->contact_number;
        $this->address = $student->address;
        $this->grade_id = $student->grade_id;

        Flux::modal('edit-student-modal')->show();
    }

    public function update()
    {
        $this->validate([
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email|unique:students,email,' . $this->editingStudent->id,
            'grade_id'  => 'required|exists:grades,id',
        ]);

        $this->editingStudent->update([
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'dob' => $this->dob,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
            'grade_id' => $this->grade_id,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        $this->resetForm();
        $this->editingStudent = null;

        Flux::modal('edit-student-modal')->close();
        
        LivewireAlert::title('Student information updated successfully!')
            ->success()
            ->toast()
            ->position('top-end')
            ->timer(5000)
            ->show();
    }

    public function save()
    {
        $this->validate([
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|email|unique:students,email',
            'dob' => 'required|date',
            'contact_number' => 'required',
            'address' => 'required|min:5',
            'grade_id'  => 'required|exists:grades,id',
        ]);

        Student::create([
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'dob' => $this->dob,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
            'grade_id' => $this->grade_id,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);

        // Reset form fields
        $this->resetForm();

        $this->resetPage();

        LivewireAlert::title('Student information saved successfully!')
            ->success()
            ->toast()
            ->position('top-end')
            ->timer(5000)
            ->show();
    }

    public function resetForm()
    {                                  
        $this->reset(['firstname', 'lastname', 'email', 'dob', 'contact_number', 'address', 'grade_id']);
    }
}; ?>

<div class="p-8">
    {{-- Header Section --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">Information</flux:heading>
            <flux:subheading>Manage your student roster and information.</flux:subheading>
        </div>
        
        <div class="flex items-center gap-4">
            <flux:input 
                wire:model.live="search" 
                placeholder="Search students..." 
                icon="magnifying-glass" 
                class="w-64"
            />
            
            <flux:modal.trigger name="add-student-modal">
                <flux:button 
                    variant="primary" 
                    icon="plus"
                    wire:click="add">
                    Add Student
                </flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- Flux Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>First Name</flux:table.column>
            <flux:table.column>Last Name</flux:table.column>
            <flux:table.column>Grade</flux:table.column>
            <flux:table.column align="center">Actions</flux:table.column>
        </flux:table.column>

        <flux:table.rows>
            @forelse ($this->students as $student)
                <flux:table.row :key="$student->id">
                    <flux:table.cell variant="strong">{{ $student->firstname }}</flux:table.cell>
                    <flux:table.cell variant="strong">{{ $student->lastname }}</flux:table.cell>
                    <flux:table.cell>{{ $student->grade->name }}</flux:table.cell>
                    <flux:table.cell align="center" class="space-x-2">
                            <flux:button 
                                wire:click="showStudent('{{ $student->id }}')"
                                variant="ghost" 
                                size="sm" 
                                icon="eye" 
                                inset="right" 
                            />
                            <flux:button 
                                wire:click="edit('{{ $student->id }}')"
                                variant="ghost" 
                                size="sm" 
                                icon="pencil-square" 
                                inset="right" 
                            />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4" align="center" class="py-12 text-zinc-400 italic">
                        No students found in the database.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
    
    {{-- Pagination --}}
    <div class="mt-6">
        {{ $this->students->links() }}
    </div>
    <flux:pagination :paginator="$this->students" />

    
    {{-- Add Student Modal --}}
    <flux:modal name="add-student-modal" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">New Enrollment</flux:heading>
                <flux:subheading>Fill in the basic information to register a student.</flux:subheading>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:input label="First name" wire:model="firstname" placeholder="e.g. John" />
                <flux:input label="Last name" wire:model="lastname" placeholder="e.g. Doe" />
            </div>

            <flux:input label="Email address" type="email" wire:model="email" icon="envelope" />
            
            <div class="grid grid-cols-3 gap-3">
                <flux:input label="Date of birth" type="date" wire:model="dob" />
                <flux:input 
                    label="Contact number" 
                    wire:model="contact_number" 
                    x-mask="(999) 999-9999"
                    placeholder="(123) 456-7890"
                    icon="phone" 
                />
                <flux:select label="Select Grade" wire:model="grade_id" placeholder="Choose grade">
                    <flux:select.option :value="null">
                        Select
                    </flux:select.option>
                    @foreach($this->grades as $grade)
                        <flux:select.option :value="$grade->id">
                            {{ $grade->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea label="Residential Address" wire:model="address" placeholder="Street, City, State..." />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2">Register Student</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="view-student-modal" class="md:w-[32rem]">
        <div class="space-y-6">
            <flux:heading size="xl">Student Details</flux:heading>

            <div class="grid grid-cols-2 gap-4">
                <div><strong>First name:</strong> {{ $viewingStudent?->firstname }}</div>
                <div><strong>Last name:</strong> {{ $viewingStudent?->lastname }}</div>
                <div><strong>Email:</strong> {{ $viewingStudent?->email }}</div>
                <div><strong>DOB:</strong> {{ $viewingStudent?->dob }}</div>
                <div><strong>Contact:</strong> {{ $viewingStudent?->contact_number }}</div>
                <div><strong>Grade:</strong> {{ $viewingStudent?->grade->name ?? '' }}</div>
                <div class="col-span-2"><strong>Address:</strong> {{ $viewingStudent?->address }}</div>
            </div>

            <div class="flex justify-end">
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="edit-student-modal" class="md:w-[32rem]">
        <div class="space-y-6">
            <flux:heading size="lg">Edit Student: {{ $firstname }}</flux:heading>

            <form wire:submit="update" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="First name" wire:model="firstname" />
                    <flux:input label="Last name" wire:model="lastname" />
                </div>

                <flux:input label="Email" wire:model="email" />

                <div class="grid grid-cols-3 gap-3">
                    <flux:input label="Date of Birth" type="date" wire:model="dob" />
                    <flux:input 
                        label="Contact number" 
                        wire:model="contact_number" 
                        x-mask="(999) 999-9999"
                        placeholder="(123) 456-7890"
                        icon="phone" 
                    />
                    <flux:select label="Select Grade" wire:model="grade_id" placeholder="Choose grade">
                        <flux:select.option :value="null">
                            Select
                        </flux:select.option>
                        @foreach($this->grades as $grade)
                            <flux:select.option :value="$grade->id">
                                {{ $grade->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:textarea label="Address" wire:model="address" />

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary">Update Student</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>