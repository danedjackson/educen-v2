<?php

use App\Models\Grade;
use App\Models\Score;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Grades')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 12;

    public ?string $name = null;

    #[Locked]
    public ?Grade $editingGrade = null;

    public function mount(): void
    {
        $this->authorizeAdmin();
        $this->perPage = config('app.page_size', 12);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function grades()
    {
        $this->authorizeAdmin();

        return Grade::query()
            ->withCount('students')
            ->when(strlen($this->search) >= 3, fn ($query) => $query->where('name', 'like', $this->search.'%'))
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function edit(string $gradeId): void
    {
        $this->authorizeAdmin();

        $this->editingGrade = Grade::findOrFail($gradeId);
        $this->name = $this->editingGrade->name;

        Flux::modal('edit-grade-modal')->show();
    }

    public function update(): void
    {
        $this->authorizeAdmin();

        if (! $this->editingGrade) {
            abort(404);
        }

        $this->validate([
            'name' => 'required|string|max:255|unique:grades,name,'.$this->editingGrade->id,
        ]);

        $this->editingGrade->update(['name' => $this->name]);

        $this->reset(['name', 'editingGrade']);
        unset($this->grades);
        Flux::modal('edit-grade-modal')->close();

        LivewireAlert::title('Grade updated successfully!')
            ->success()
            ->toast()
            ->position('top-end')
            ->timer(config('app.toast_duration'))
            ->show();
    }

    public function delete(string $gradeId): void
    {
        $this->authorizeAdmin();

        $grade = Grade::findOrFail($gradeId);

        if (
            $grade->students()->exists()
            || Score::where('grade_id', $grade->id)->exists()
            || Grade::where('next_grade_id', $grade->id)->exists()
        ) {
            LivewireAlert::title('This grade cannot be deleted while it is in use.')
                ->error()
                ->toast()
                ->position('top-end')
                ->timer(config('app.toast_duration'))
                ->show();

            return;
        }

        $grade->delete();
        unset($this->grades);

        LivewireAlert::title('Grade deleted successfully!')
            ->success()
            ->toast()
            ->position('top-end')
            ->timer(config('app.toast_duration'))
            ->show();
    }

    private function authorizeAdmin(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user?->hasRole('admin'), 403);
    }
};
?>

<div class="p-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">Grades</flux:heading>
            <flux:subheading>Manage the current grade levels.</flux:subheading>
        </div>

        <div class="flex items-center gap-4">
        <flux:input 
            wire:model.live.debounce.300ms="search" 
            placeholder="Search grades..." 
            icon="magnifying-glass" 
            class="w-64" />
        </div>
    </div>

    <flux:table>
        <flux:table.columns sticky>
            <flux:table.column>Grade</flux:table.column>
            <flux:table.column align="center">Students</flux:table.column>
            <flux:table.column align="center">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->grades as $grade)
                <flux:table.row :key="$grade->id">
                    <flux:table.cell variant="strong">{{ $grade->name }}</flux:table.cell>
                    <flux:table.cell align="center">{{ $grade->students_count }}</flux:table.cell>
                    <flux:table.cell align="center" class="space-x-2">
                        <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit('{{ $grade->id }}')" />
                        <flux:button
                            size="sm"
                            icon="trash"
                            variant="ghost"
                            class="text-red-500 hover:text-red-600"
                            wire:confirm="Are you sure you want to delete this grade? Grades used by students or scores cannot be deleted."
                            wire:click="delete('{{ $grade->id }}')"
                        />
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3" align="center" class="py-12 text-zinc-400 italic">
                        No grades found.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-6">
        <flux:pagination :paginator="$this->grades" />
    </div>

    <flux:modal name="edit-grade-modal" class="md:w-[32rem]">
        <form wire:submit="update" class="space-y-6">
            <div>
                <flux:heading size="lg">Edit Grade</flux:heading>
                <flux:subheading>Update the grade name.</flux:subheading>
            </div>

            <flux:input label="Grade name" wire:model="name" />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2">Save Changes</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
