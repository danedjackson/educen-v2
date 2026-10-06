<?php

use App\Models\LessonPlan;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
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

    // Keep the selected review record server-controlled between Livewire requests.
    #[Locked]
    public ?LessonPlan $reviewingPlan = null;

    public $revisedDocument;

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function mount(): void
    {
        abort_unless($this->user()->hasAnyRole(['teacher', 'admin']), 403);
        $this->perPage = config('app.page_size', 12);
    }

    #[Computed]
    public function isAdmin(): bool
    {
        return $this->user()->hasRole('admin');
    }

    #[Computed]
    public function plans()
    {
        $query = LessonPlan::query()->with('teacher');

        // Teachers only see their own submissions; administrators review the full queue.
        if (! $this->isAdmin) {
            $query->where('teacher_id', $this->user()->ulid);
        }

        return $query->latest()->paginate($this->perPage);
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

    public function review(string $planId): void
    {
        abort_unless($this->isAdmin, 403);

        $this->reviewingPlan = LessonPlan::with('teacher')->findOrFail($planId);
        $this->reset('revisedDocument');

        Flux::modal('review-lesson-plan-modal')->show();
    }

    public function uploadRevision(): void
    {
        abort_unless($this->isAdmin, 403);

        $plan = $this->pendingReviewPlan();
        $this->validate([
            'revisedDocument' => 'required|file|mimes:docx|max:12288',
        ]);

        $this->storeRevision($plan);
        $this->reset('revisedDocument');
        $this->refreshReview();

        LivewireAlert::title('Revised lesson plan uploaded.')
            ->success()
            ->toast()
            ->position('top-end')
            ->timer(config('app.toast_duration'))
            ->show();
    }

    public function markDone(): void
    {
        abort_unless($this->isAdmin, 403);

        $plan = $this->pendingReviewPlan();

        // If a revision is selected, save it before recording the review as complete.
        if ($this->revisedDocument) {
            $this->validate([
                'revisedDocument' => 'required|file|mimes:docx|max:12288',
            ]);
            $this->storeRevision($plan);
            $plan->refresh();
        }

        $plan->update([
            'status' => 'done',
            'completed_by' => $this->user()->ulid,
            'completed_at' => now(),
        ]);

        $this->reset(['reviewingPlan', 'revisedDocument']);
        unset($this->plans);
        Flux::modal('review-lesson-plan-modal')->close();

        LivewireAlert::title('Lesson plan marked as done.')
            ->success()
            ->toast()
            ->position('top-end')
            ->timer(config('app.toast_duration'))
            ->show();
    }

    private function pendingReviewPlan(): LessonPlan
    {
        abort_unless($this->reviewingPlan, 404);

        $plan = LessonPlan::findOrFail($this->reviewingPlan->id);
        // Re-fetch and check status so stale modal state cannot modify a completed plan.
        abort_unless($plan->status === 'pending', 403);

        return $plan;
    }

    private function storeRevision(LessonPlan $plan): void
    {
        $filename = $this->safeFilename($this->revisedDocument->getClientOriginalName());
        $path = $this->revisedDocument->store('lesson-plans/'.$plan->teacher_id.'/revisions', 'local');
        abort_if($path === false, 500, 'The revised lesson plan could not be stored.');

        $plan->update([
            'revised_path' => $path,
            'revised_filename' => $filename,
        ]);
    }

    private function refreshReview(): void
    {
        $this->reviewingPlan = $this->reviewingPlan->fresh('teacher');
        unset($this->plans);
    }

    private function safeFilename(string $filename): string
    {
        return basename(str_replace('\\', '/', $filename));
    }
};
?>

<div class="space-y-8">
    @if (! $this->isAdmin)
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
    @endif

    <section>
        <div class="mb-4">
            <flux:heading size="lg">{{ $this->isAdmin ? 'Lesson plans for review' : 'Your lesson plans' }}</flux:heading>
            <flux:subheading>
                {{ $this->isAdmin ? 'Review teacher submissions, upload changes if needed, and mark plans done.' : 'Recently uploaded lesson plans and their review status.' }}
            </flux:subheading>
        </div>

        <flux:table>
            <flux:table.columns sticky>
                @if ($this->isAdmin)
                    <flux:table.column>Teacher</flux:table.column>
                @endif
                <flux:table.column>Lesson plan</flux:table.column>
                <flux:table.column>Document</flux:table.column>
                <flux:table.column>Uploaded</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="center">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->plans as $plan)
                    <flux:table.row :key="$plan->id">
                        @if ($this->isAdmin)
                            <flux:table.cell variant="strong">{{ $plan->teacher->name }}</flux:table.cell>
                        @endif
                        <flux:table.cell variant="strong">{{ $plan->title }}</flux:table.cell>
                        <flux:table.cell>{{ $plan->currentFilename() }}</flux:table.cell>
                        <flux:table.cell>{{ $plan->created_at->format('d-M-Y H:i') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$plan->status === 'done' ? 'green' : 'amber'">
                                {{ ucfirst($plan->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="center" class="space-x-2">
                            {{-- Teachers can download their own current file; admins get this action only after review. --}}
                            @if (! $this->isAdmin || $plan->status === 'done')
                                <flux:button
                                    size="sm"
                                    variant="ghost"
                                    icon="arrow-down-tray"
                                    :href="route('lesson-plans.download', ['lessonPlan' => $plan->id, 'version' => 'current'])"
                                    title="Download current document"
                                />
                            @endif
                            @if ($this->isAdmin && $plan->status === 'pending')
                                <flux:button size="sm" variant="primary" wire:click="review('{{ $plan->id }}')">
                                    Review
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell :colspan="$this->isAdmin ? 6 : 5" align="center" class="py-12 text-zinc-400 italic">
                            {{ $this->isAdmin ? 'No lesson plans have been submitted.' : 'You have not uploaded any lesson plans yet.' }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-6">
            <flux:pagination :paginator="$this->plans" />
        </div>
    </section>

    <flux:modal name="review-lesson-plan-modal" class="md:w-[36rem]">
        @if ($reviewingPlan)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Review lesson plan</flux:heading>
                    <flux:subheading>{{ $reviewingPlan->title }} — {{ $reviewingPlan->teacher->name }}</flux:subheading>
                </div>

                <div class="flex flex-wrap gap-3">
                    <flux:button
                        variant="filled"
                        icon="arrow-down-tray"
                        :href="route('lesson-plans.download', ['lessonPlan' => $reviewingPlan->id, 'version' => 'original'])"
                    >
                        Download original
                    </flux:button>
                    @if ($reviewingPlan->revised_path)
                        <flux:button
                            variant="filled"
                            icon="arrow-down-tray"
                            :href="route('lesson-plans.download', ['lessonPlan' => $reviewingPlan->id, 'version' => 'current'])"
                        >
                            Download latest revision
                        </flux:button>
                    @endif
                </div>

                <form wire:submit="uploadRevision" class="space-y-4">
                    <flux:input type="file" label="Upload a revised DOCX (optional)" wire:model="revisedDocument" accept=".docx" />
                    @error('revisedDocument')
                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                    @enderror
                    <div class="flex items-center justify-between gap-3">
                        <flux:button type="submit" variant="filled" icon="arrow-up-tray">Upload Changes</flux:button>
                        <flux:button type="button" variant="primary" icon="check" wire:click="markDone">
                            Mark as Done
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>
</div>
