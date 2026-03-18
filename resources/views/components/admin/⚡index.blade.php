<?php

use App\Models\Grade;
use App\Models\SchoolYearAdvancement;
use App\Models\Student;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

new #[Title("Administration Panel")] class extends Component
{
    public ?User $editingUser = null;
    public $perPage, $name, $email;
    public $selectedRoles = [];
    public $selectedGrades = [];

    public function mount()
    {
        $this->perPage = config('app.page_size', 10);
    }

    #[Computed]
    public function roles()
    {
        return Role::all();
    }

    #[Computed]
    public function grades()
    {
        return Grade::all();
    }

    #[Computed]
    public function users()
    {
        return User::orderBy('name')->paginate($this->perPage);
    }

    #[Computed]
    public function advanceSchoolYearLog()
    {
        return SchoolYearAdvancement::orderBy('advanced_at', 'desc')->paginate($this->perPage);
    }

    public function confirmUser($userId): void
    {
        $user = User::find($userId);
        $user->update([
            'user_confirmed_at' => $user->user_confirmed_at ? null : now()
        ]);
    }

    public function editUser(User $user)
    {
        $this->editingUser = $user;
        $this->name = $user->name;
        $this->email = $user->email;

        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        $this->selectedGrades = $user->grades->pluck('id')->toArray();

        Flux::modal('edit-user-modal')->show();
    }

    public function update()
    {
        if (!$this->editingUser) {
            return;
        }

        $this->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $this->editingUser->ulid . ',ulid',
        ]);

        $this->editingUser->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        if(method_exists($this->editingUser, 'syncRoles')) {
            $this->editingUser->syncRoles($this->selectedRoles);
        }
        if (method_exists($this->editingUser, 'grades')) {
            $this->editingUser->grades()->sync($this->selectedGrades);
        }

        $this->resetForm();
        $this->editingUser = null;

        Flux::modal('edit-user-modal')->close();

        LivewireAlert::title('User updated successfully!')
            ->success()
            ->toast()
            ->position('top-end')
            ->timer(config('app.toast_duration'))
            ->show();
    }

    public function checkStudentAdvancementStatus()
    {
        $nonAdvancing = Student::where('is_deleted', false)
            ->where('is_advancing', false)
            ->with('grade') // Assuming a relationship exists
            ->get();
        $count = $nonAdvancing->count();

        if ($count > 0) {
            // Create a scrollable list or table for the alert
            $studentList = $nonAdvancing->map(function ($student) {
                
                return "<li><b>{$student->fullName()}</b> (Grade {$student->grade->name})</li>";
            })->implode('');

            $htmlContent = "
                <p>There are {$count} students <b>not</b> marked for advancement:</p>
                <div style='max-height: 200px; overflow-y: auto; text-align: left; margin-top: 10px; padding: 10px; border: 1px solid #eee; border-radius: 5px;'>
                    <ul style='margin: 0; padding-left: 20px; font-size: 0.9em;'>
                        {$studentList}
                    </ul>
                </div>
                <p style='margin-top: 15px;'>Are you sure you want to proceed anyway?</p>
            ";

            LivewireAlert::title('Incomplete Advancement List')
                ->html($htmlContent) // Use ->html() instead of ->text()
                ->warning()
                ->withConfirmButton('Yes, continue')
                ->withCancelButton('No, let me review')
                ->onConfirm('advanceSchoolYearConfirmation')
                ->timer(null)
                ->show();
        }
        else {
            $this->advanceSchoolYearConfirmation();
        }
    }

    public function advanceSchoolYearConfirmation()
    {
        LivewireAlert::title('Are you sure you want to advance the school year?')
            ->text('This will advance all students to the next grade level which cannot be undone..')
            ->question()
            ->withConfirmButton('Yes, advance year')
            ->withCancelButton('No, stay current')
            ->onConfirm('advanceSchoolYear')
            ->timer(null)
            ->show();
    }
    public function advanceSchoolYear()
    {
        $students = Student::where(['is_deleted' => false, 'is_advancing' => true]);

        $students->each(function ($student) {
            // Get the next grade id from the current grade
            $nextGradeId = $student->grade->next_grade_id ?? null;
            // if the next grade id is null, it means the student is in the highest grade and should be marked as deleted (graduated)
            if (!$nextGradeId) {
                $student->update([
                    'is_deleted' => true,
                    'is_advancing' => false,
                    'updated_by' => Auth::user()->ulid,
                    'updated_at' => now(),
                ]);
                return;
            }
            // Otherwise, advance the student to the next grade
            $student->update([
                'grade_id' => $nextGradeId,
                'is_advancing' => true,
                'updated_by' => Auth::user()->ulid,
                'updated_at' => now(),
            ]);
        });
        // Create a new school year advancement record
        SchoolYearAdvancement::create([
            'advanced_by' => Auth::user()->ulid,
        ]);
    }
    public function resetForm()
    {
        $this->reset(['name', 'email', 'selectedRoles', 'selectedGrades']);
    }
};
?>

<div x-data="{ activeTab: 'users' }">
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="activeTab = 'users'"
                    :class="activeTab === 'users' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Users
            </button>
            <button @click="activeTab = 'advance-year'"
                    :class="activeTab === 'advance-year' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                School Year Advancement Log
            </button>

            {{-- Add other tabs here --}}
        </nav>
    </div>

    <div class="mt-6">
        <div x-show="activeTab === 'users'">
            <!-- Users table -->
            <flux:table>
                <flux:table.columns sticky>
                    <flux:table.column>Name</flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column>Email Verified</flux:table.column>
                    <flux:table.column>User Confirmed</flux:table.column>
                    <flux:table.column>Creation Date</flux:table.column>
                    <flux:table.column align="center">Mark Confirmed</flux:table.column>
                    <flux:table.column align="center">Actions</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->users as $user)
                        <flux:table.row wire:key="user-{{ $user->ulid }}" :key="$user->ulid">
                            <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>{{ $user->email_verified_at ? 'Yes' : 'No' }}</flux:table.cell>
                            <flux:table.cell>{{ $user->user_confirmed_at ? \Carbon\Carbon::parse($user->user_confirmed_at)->format('d-M-Y') : 'No' }}</flux:table.cell>
                            <flux:table.cell>{{ \Carbon\Carbon::parse($user->created_at)->format('d-M-Y') }}</flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:switch
                                    :checked="!!$user->user_confirmed_at"
                                    wire:click="confirmUser('{{ $user->ulid }}')"
                                />
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:button
                                    wire:click="editUser('{{ $user->ulid }}')"
                                    variant="ghost"
                                    size="sm"
                                    icon="pencil-square"
                                    inset="right"
                                />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" align="center" class="py-12 text-zinc-400 italic">
                                No users found in the database.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
            <flux:pagination :paginator="$this->users" />
        </div>
        <div x-show="activeTab === 'advance-year'" class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="xl">School Year Advancement</flux:heading>
                    <flux:subheading>Manage the transition of students to their next grade level.</flux:subheading>
                </div>

                <flux:button 
                    variant="primary" 
                    icon="arrow-up-circle"
                    wire:click="checkStudentAdvancementStatus">
                    Advance School Year
                </flux:button>
            </div>

            <div>
                <flux:table>
                    <flux:table.columns sticky>
                        <flux:table.column>Advanced At</flux:table.column>
                        <flux:table.column>Advanced By</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->advanceSchoolYearLog as $log)
                            <flux:table.row wire:key="advancement-{{ $log->id }}">
                                <flux:table.cell variant="strong">
                                    {{ \Carbon\Carbon::parse($log->advanced_at)->format('d-M-Y H:i') }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ $log->user?->name ?? 'Unknown' }}
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="2" align="center" class="py-12 text-zinc-400 italic">
                                    No school year advancements recorded yet.
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>

                <div class="mt-4">
                    <flux:pagination :paginator="$this->advanceSchoolYearLog" />
                </div>
            </div>
        </div>
    </div>

    {{-- Edit User Modal --}}
    <flux:modal name="edit-user-modal" class="md:w-[32rem]">
        <form wire:submit="update" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit User</flux:heading>
            </div>

            <flux:input label="Name" wire:model="name" />
            <flux:input label="Email address" type="email" wire:model="email" icon="envelope" />

            {{-- Role Selection Section --}}
            <flux:checkbox.group label="Assign Roles" wire:model="selectedRoles">
                <div class="grid grid-cols-2 gap-2 mt-2">
                    @foreach($this->roles as $role)
                        <flux:checkbox 
                            :value="$role->name" 
                            :label="ucfirst($role->name)" 
                        />
                    @endforeach
                </div>
            </flux:checkbox.group>

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
                <flux:button type="submit" variant="primary" class="ml-2">Update User</flux:button>
            </div>
        </form>
    </flux:modal>
</div>