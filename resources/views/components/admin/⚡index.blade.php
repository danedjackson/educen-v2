<?php

use App\Models\User;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title("Administration Panel")] class extends Component
{
    public ?User $editingUser = null;
    public $name;
    public $email;

    #[Computed]
    public function users()
    {
        return User::orderBy('name')->get();
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

    public function resetForm()
    {
        $this->reset(['name', 'email']);
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
                            <flux:table.cell>{{ $user->user_confirmed_at ? Carbon::parse($user->user_confirmed_at)->format('d-M-Y') : 'No' }}</flux:table.cell>
                            <flux:table.cell>{{ Carbon::parse($user->created_at)->format('d-M-Y') }}</flux:table.cell>
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