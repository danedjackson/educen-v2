<?php

use App\Models\Student;
use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Students')] class extends Component {

    public $students = [];

    public $firstname, $lastname, $email, $dob, $contact_number, $address;
    
    public function mount()
    {
        $this->students = Student::latest()->get();
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
        ]);

        Student::create([
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'dob' => $this->dob,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
        ]);

        // Reset form fields
        $this->reset(['firstname', 'lastname', 'email', 'dob', 'contact_number', 'address']);

        // Refresh the students list
        $this->students = Student::latest()->get();
    }
}; ?>

<div class="p-8">
    {{-- Header Section --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">Students</flux:heading>
            <flux:subheading>Manage your student roster and information.</flux:subheading>
        </div>
        
        <flux:modal.trigger name="add-student-modal">
            <flux:button variant="primary" icon="plus">Add Student</flux:button>
        </flux:modal.trigger>
    </div>

    {{-- Flux Table --}}
    <flux:table>
        <flux:table.columns>
            <flux:table.column>First Name</flux:table.column>
            <flux:table.column>Last Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Contact</flux:table.column>
            <flux:table.column>Grade</flux:table.column>
            <flux:table.column align="end"></flux:table.column>
        </flux:table.column>

        <flux:table.rows>
            @forelse ($students as $student)
                <flux:table.row :key="$student->id">
                    <flux:table.cell variant="strong">{{ $student->firstname }}</flux:table.cell>
                    <flux:table.cell variant="strong">{{ $student->lastname }}</flux:table.cell>
                    <flux:table.cell>{{ $student->email }}</flux:table.cell>
                    <flux:table.cell>{{ $student->contact_number }}</flux:table.cell>
                    <flux:table.cell>{{ $student->grade }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button variant="ghost" size="sm" icon="pencil-square" inset="right" />
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
            
            <div class="grid grid-cols-2 gap-4">
                <flux:input label="Date of birth" type="date" wire:model="dob" />
                <flux:input label="Contact number" wire:model="contact_number" icon="phone" />
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
</div>