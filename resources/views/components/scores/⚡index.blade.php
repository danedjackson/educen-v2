<?php

use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use App\Models\Student;
use App\Models\Subject;
use App\Models\AssignmentType;
use App\Models\Score;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;

new #[Title("Scores")] class extends Component
{
    use WithPagination;

    // search/filter and pagination
    public $search = '';
    public $perPage = 12;
    // student selected by clicking a card
    public ?Student $selectedStudent = null;
    // subject chosen within modal (used to drill into individual scores)
    public ?Subject $selectedSubject = null;

    // --- fields for adding a new score ---
    public $subjectId;
    public $assignmentTypeId;
    public $scoreValue;
    public $dateAdministered;
    public $comments;

    public $subjects = [];
    public $assignmentTypes = [];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    function students()
    {
        // basic search by name or email
        $query = Student::query();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Now VS Code knows $user has the hasRole method
        if (! $user->hasRole('admin')) {
            $gradeIds = Auth::user()->grades->pluck('id');
            $query->whereIn('grade_id', $gradeIds);
        }

        if(strlen($this->search) >= 3) {
            $query->where(function ($q) {
                $q->where('firstname', 'like', $this->search . '%')
                ->orWhere('lastname', 'like', $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate($this->perPage);
    }

    public function showScores(Student $student)
    {
        // eager load relations so the modal can access them without another query
        $this->selectedStudent = $student->load(['scores.subject', 'scores.teacher', 'scores.assignmentType']);
        // reset any previous subject selection when opening for a new student
        $this->selectedSubject = null;

        // populate dropdown options for the add score form
        $this->subjects = Subject::all();
        $this->assignmentTypes = AssignmentType::all();
        $this->resetScoreForm();

        Flux::modal('view-scores-modal')->show();
    }

    public function selectSubject($subjectId)
    {
        $this->selectedSubject = Subject::find($subjectId);
    }

    public function clearSubject()
    {
        $this->selectedSubject = null;
    }

    #[Computed]
    function subjectAverages()
    {
        if (! $this->selectedStudent) {
            return collect();
        }

        return $this->selectedStudent->scores
                    ->groupBy('subject_id')
                    ->map(fn($scores) => [
                        'subject' => $scores->first()->subject,
                        'average' => $scores->avg('score'),
                    ])
                    ->values();
    }

    public function showAddScore()
    {
        // ensure form is clean
        $this->resetScoreForm();
        Flux::modal('add-score-modal')->show();
    }

    protected function resetScoreForm()
    {
        $this->reset(['subjectId', 'assignmentTypeId', 'scoreValue', 'dateAdministered', 'comments']);
    }

    public function saveScore()
    {
        $this->validate([
            'subjectId' => 'required|exists:subjects,id',
            'assignmentTypeId' => 'required|exists:assignment_types,id',
            'scoreValue' => 'required|numeric|min:0',
            'dateAdministered' => 'nullable|date',
            'comments' => 'nullable|string',
        ], [
            'subjectId.required' => 'Please select a subject for this assignment.',
            'subjectId.exists' => 'The selected subject is invalid.',
            'assignmentTypeId.required' => 'You must choose an assignment type (e.g., Quiz, Homework).',
            'scoreValue.required' => 'The score cannot be empty.',
            'scoreValue.numeric' => 'The score must be a number.',
            'scoreValue.min' => 'The score cannot be less than zero.',
            'dateAdministered.date' => 'Please provide a valid date for when the assignment was administered.',
        ]);

        Score::create([
            'student_id' => $this->selectedStudent->id,
            'subject_id' => $this->subjectId,
            'assignment_type_id' => $this->assignmentTypeId,
            'score' => $this->scoreValue,
            'date_administered' => $this->dateAdministered,
            'comments' => $this->comments,
            'grade_id' => $this->selectedStudent->grade_id,
            'teacher_id' => Auth::id(),
            'created_at' => now(),
        ]);

        // reload student scores so view updates immediately
        $this->selectedStudent = $this->selectedStudent->fresh(['scores.subject','scores.teacher','scores.assignmentType']);
        Flux::modal('add-score-modal')->close();

        LivewireAlert::title('Score added successfully!')
            ->success()
            ->toast()
            ->position('top-end')
            ->timer(5000)
            ->show();
    }
};
?>

<div class="p-8">
    {{-- Header with search and instructions --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <flux:heading size="xl">Student Scores</flux:heading>
            <flux:subheading>Click a student to view their scores.</flux:subheading>
        </div>
        <div class="flex items-center gap-4">
            <flux:input
                wire:model.live="search"
                placeholder="Search students..."
                icon="magnifying-glass"
                class="w-64"
            />
        </div>
    </div>

    {{-- Student cards grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse ($this->students as $student)
            <div
                wire:click="showScores('{{ $student->id }}')"
                class="p-4 border rounded-lg shadow-sm hover:shadow-md cursor-pointer transition"
                :key="$student->id"
            >
                <div class="font-medium text-lg">{{ $student->full_name }}</div>
                <div class="text-sm text-zinc-500">Grade {{ $student->grade?->name }}</div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-zinc-400 italic">
                No students found.
            </div>
        @endforelse
    </div>

    {{-- Pagination controls --}}
    <div class="mt-6">
        {{ $this->students->links() }}
    </div>

    {{-- Modal showing scores for selected student --}}
    <flux:modal name="view-scores-modal" class="md:w-[34rem]">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
            <flux:heading size="xl">
                @if($selectedSubject)
                    Scores for {{ $selectedStudent?->full_name }} – {{ $selectedSubject->name }}
                @else
                    Subjects averages for {{ $selectedStudent?->full_name }}
                @endif
            </flux:heading>
        </div>

            @if($selectedSubject)
                <div class="mb-4">
                    <flux:button wire:click="clearSubject()" variant="ghost">&larr; Back to subjects</flux:button>
                </div>

                @php
                    $scores = $selectedStudent->scores->where('subject_id', $selectedSubject->id);
                @endphp

                @if($scores->isNotEmpty())
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Assignment Type</flux:table.column>
                            <flux:table.column>Score</flux:table.column>
                            <flux:table.column>Date Administered</flux:table.column>
                            <flux:table.column>Teacher</flux:table.column>
                            <flux:table.column>Comments</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($scores as $score)
                                <flux:table.row :key="$score->id">
                                    <flux:table.cell>{{ $score->assignmentType?->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $score->score }}</flux:table.cell>
                                    <flux:table.cell>{{ $score->date_administered ? $score->date_administered->format('M j, Y') : '-' }}</flux:table.cell>
                                    <flux:table.cell>{{ $score->teacher?->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $score->comments }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <div class="text-center text-zinc-500 italic">
                        No scores recorded for this subject.
                    </div>
                @endif
            @else
                @if($selectedStudent && $selectedStudent->scores->isNotEmpty())
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Subject</flux:table.column>
                            <flux:table.column>Average Score</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->subjectAverages as $item)
                                <flux:table.row wire:click="selectSubject('{{ $item['subject']->id }}')" class="cursor-pointer hover:bg-zinc-50" :key="$item['subject']->id">
                                    <flux:table.cell>{{ $item['subject']->name }}</flux:table.cell>
                                    <flux:table.cell>{{ number_format($item['average'], 2) }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <div class="text-center text-zinc-500 italic">
                        No scores recorded for this student.
                    </div>
                @endif
            @endif
            <div class="flex justify-between">
                @if($selectedStudent)
                    <flux:button variant="primary" wire:click="showAddScore">Add Score</flux:button>
                @endif
                <flux:modal.close>
                    <flux:button variant="ghost">Close</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Add score modal --}}
    <flux:modal name="add-score-modal" class="md:w-[32rem]">
        <form wire:submit.prevent="saveScore" class="space-y-6">
            <div>
                <flux:heading size="lg">Record New Score for {{ $selectedStudent?->full_name }}</flux:heading>
            </div>

            <flux:select label="Subject" wire:model="subjectId">
                <flux:select.option :value="null">Select subject</flux:select.option>
                @foreach($this->subjects as $subject)
                    <flux:select.option :value="$subject->id">{{ $subject->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select label="Assignment Type" wire:model="assignmentTypeId">
                <flux:select.option :value="null">Select type</flux:select.option>
                @foreach($this->assignmentTypes as $type)
                    <flux:select.option :value="$type->id">{{ $type->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input label="Score" type="number" wire:model="scoreValue" min="0" step="0.01" />
            <flux:input label="Date Administered" type="date" wire:model="dateAdministered" />
            <flux:textarea label="Comments" wire:model="comments" />

            <div class="flex">
                <flux:spacer />
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="ml-2">Save Score</flux:button>
            </div>
        </form>
    </flux:modal>
</div>