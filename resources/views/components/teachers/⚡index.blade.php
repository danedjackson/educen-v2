<?php

use App\Models\Grade;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Computed;
use App\Mail\TeacherRegistered;
use Illuminate\Support\Facades\Password;

new #[Title('Teachers')] class extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 12;

    // form fields for adding a teacher
    public ?string $name = '';
    public ?string $email = '';
    public array $selectedGrades = [];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->perPage = config('app.page_size', 12);
    }

    public function getTeachersProperty()
    {
        $query = User::query()->whereHas('roles', function ($q) {
            $q->where('name', 'teacher');
        });

        if (strlen($this->search) >= 3) {
            $query->where(function ($q) {
                $q->where('name', 'like', $this->search . '%')
                    ->orWhere('email', 'like', $this->search . '%');
            });
        }

        return $query->orderBy('name')->paginate($this->perPage);
    }

    #[Computed]
    public function grades()
    {
        return Grade::orderBy('name')->get();
    }

    public function add()
    {
        $this->resetForm();
        Flux::modal('add-teacher-modal')->show();
    }

    protected function resetForm()
    {
        $this->reset(['name', 'email', 'selectedGrades']);
    }

    public function save()
    {
        $this->validate([
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'selectedGrades' => 'nullable|array',
        ], [
            'name.required' => 'A name is required to create a teacher.',
            'email.required' => 'An email address is required to create a teacher.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'A user with this email already exists.',
        ]);

        // create user with a random password and assign teacher role (if available)
        $password = Str::random(8);
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($password),
            'created_by' => Auth::id(),
            'created_at' => now(),
        ]);
        // attach grades if provided
        foreach ($this->selectedGrades ?? [] as $gradeId) {
            $user->grades()->attach($gradeId);
        }

        try {
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('teacher');
            }
        } catch (\Throwable $e) {
            // ignore if role package not configured
        }

        // create a password reset token for the user
        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker();
        $token = $broker->createToken($user);

        // build the Fortify reset URL (named route `password.reset`)
        $link = route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);

        Mail::to($this->email)->queue(new TeacherRegistered($this->email, $password, $link));

        $this->resetForm();
        Flux::modal('add-teacher-modal')->close();

        LivewireAlert::title('Teacher created successfully!')->success()->toast()->show();
    }

    public function sendRegistrationEmail()
    {
        $this->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Please enter the teacher email address before sending the registration email.',
        ]);

        

        LivewireAlert::title('Registration email sent')->success()->toast()->show();
    }
};
?>

<div class="p-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">Teachers</flux:heading>
            <flux:subheading>Manage teacher accounts and invitations.</flux:subheading>
        </div>
        <div class="flex items-center gap-4">
            <flux:input wire:model.live="search" placeholder="Search teachers..." icon="magnifying-glass" class="w-64" />
            <flux:modal.trigger name="add-teacher-modal">
                <flux:button variant="primary" icon="plus" wire:click="add">Add Teacher</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- Teachers Flux Table --}}
    <flux:table>
        <flux:table.columns sticky>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column align="center">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->teachers as $teacher)
                <flux:table.row :key="$teacher->id">
                    <flux:table.cell variant="strong">{{ $teacher->name }}</flux:table.cell>
                    <flux:table.cell>{{ $teacher->email }}</flux:table.cell>
                    <flux:table.cell align="center" class="space-x-2">
                        <flux:button size="sm" variant="ghost" wire:click="edit('{{ $teacher->id }}')" icon="pencil-square" />
                        <flux:button size="sm" icon="trash" variant="ghost" class="text-red-500 hover:text-red-600" wire:confirm="Are you sure you want to delete this teacher?" wire:click="deleteTeacher('{{ $teacher->id }}')" />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3" align="center" class="py-12 text-zinc-400 italic">No teachers found.</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-6">
        {{ $this->teachers->links() }}
    </div>

    {{-- Add Teacher Modal --}}
    <flux:modal name="add-teacher-modal" class="md:w-[32rem]">
        <form wire:submit.prevent="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Invite New Teacher</flux:heading>
                <flux:subheading>Provide an email to create the account and optionally send a registration link.</flux:subheading>
            </div>

            <flux:input label="Full name" wire:model="name" />

            <div class="flex items-start gap-3">
                <flux:input label="Email" wire:model="email" class="flex-1" />
                
            </div>

            {{-- Grade Selection Section --}}
            <flux:checkbox.group label="Assign Grades" wire:model="selectedGrades">
                <div class="grid grid-cols-2 gap-2 mt-2">
                    @foreach($this->grades as $grade)
                        <flux:checkbox 
                            :value="$grade->id" 
                            :label="$grade->name" 
                        />
                    @endforeach
                </div>
            </flux:checkbox.group>

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2">Create Teacher</flux:button>
            </div>
        </form>
    </flux:modal>
</div>