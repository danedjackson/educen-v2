<?php

use App\Models\LessonPlan;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Lesson Plans')] class extends Component
{
    use WithFileUploads;
    use WithPagination;

    public int $perPage = 12;

    public ?string $title = null;

    public $document;

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function mount(): void
    {
        abort_unless($this->user()->hasRole('teacher'), 403);
        $this->perPage = config('app.page_size', 12);
    }

    public function getPlansProperty()
    {
        return LessonPlan::query()
            ->where('teacher_id', $this->user()->ulid)
            ->latest()
            ->paginate($this->perPage);
    }

    public function uploadPlan(): void
    {
        abort_unless($this->user()->hasRole('teacher'), 403);

        $this->validate([
            'title' => 'required|string|max:255',
            'document' => 'required|file|mimes:docx|max:12288',
        ]);

        $filename = $this->safeFilename($this->document->getClientOriginalName());
        // Lesson plans stay on the private disk and are served through the authorized download route.
        $path = $this->document->store('lesson-plans/'.$this->user()->ulid, 'local');
        abort_if($path === false, 500, 'The lesson plan could not be stored.');

        LessonPlan::create([
            'teacher_id' => $this->user()->ulid,
            'title' => $this->title,
            'original_path' => $path,
            'original_filename' => $filename,
        ]);

        $this->reset(['title', 'document']);
        $this->resetPage();
        unset($this->plans);
        Flux::modal('upload-lesson-plan-modal')->close();

        LivewireAlert::title('Lesson plan uploaded successfully.')
            ->success()
            ->toast()
            ->position('top-end')
            ->timer(config('app.toast_duration'))
            ->show();
    }

    private function safeFilename(string $filename): string
    {
        return basename(str_replace('\\', '/', $filename));
    }
};
?>

<div class="space-y-8">
    <div>
        <flux:modal.trigger name="upload-lesson-plan-modal">
            <flux:button variant="primary" icon="plus">Upload Lesson Plan</flux:button>
        </flux:modal.trigger>

        <flux:modal name="upload-lesson-plan-modal" class="md:w-[32rem]">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Upload a lesson plan</flux:heading>
                    <flux:subheading>Upload your plan as a DOCX document for administrator review.</flux:subheading>
                </div>

                <form wire:submit="uploadPlan" class="space-y-4">
                    <flux:input label="Lesson plan title" wire:model="title" />
                    <flux:input type="file" label="DOCX document" wire:model="document" accept=".docx" />
                    @error('document')
                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                    @enderror
                    <div class="flex items-center gap-3">
                        <flux:button type="submit" variant="primary" icon="arrow-up-tray">Upload Lesson Plan</flux:button>
                        <flux:icon.loading wire:loading wire:target="document,uploadPlan" />
                    </div>
                </form>
            </div>
        </flux:modal>
    </div>

    <section>
        <div class="mb-4">
            <flux:heading size="lg">Your lesson plans</flux:heading>
            <flux:subheading>Recently uploaded lesson plans and their review status.</flux:subheading>
        </div>

        <flux:table>
            <flux:table.columns sticky>
                <flux:table.column>Lesson plan</flux:table.column>
                <flux:table.column>Document</flux:table.column>
                <flux:table.column>Uploaded</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="center">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->plans as $plan)
                    <flux:table.row :key="$plan->id">
                        <flux:table.cell variant="strong">{{ $plan->title }}</flux:table.cell>
                        <flux:table.cell>{{ $plan->currentFilename() }}</flux:table.cell>
                        <flux:table.cell>{{ $plan->created_at->format('d-M-Y H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$plan->status === 'done' ? 'green' : 'amber'">
                                {{ ucfirst($plan->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="center">
                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="arrow-down-tray"
                                :href="route('lesson-plans.download', ['lessonPlan' => $plan->id, 'version' => 'current'])"
                                title="Download current document"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" align="center" class="py-12 text-zinc-400 italic">
                            You have not uploaded any lesson plans yet.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-6">
            <flux:pagination :paginator="$this->plans" />
        </div>
    </section>
</div>
