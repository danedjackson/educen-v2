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

    public function updatedSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    function students()
    {
        // basic search by name or email
        $query = Student::query();

        // limit to the grades the current user is assigned to (unless admin)
        if (! Auth::user()->hasRole('admin')) {
            $gradeIds = Auth::user()->grades->pluck('id');
            $query->whereIn('grade_id', $gradeIds);
        }

        $query->where(function ($q) {
            $q->where('firstname', 'like', $this->search . '%')
              ->orWhere('lastname', 'like', $this->search . '%')
              ->orWhere('email', 'like', '%' . $this->search . '%');
        });

        return $query->latest()->paginate($this->perPage);
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

    // --- fields for adding a new score ---
    public $subject_id;
    public $assignment_type_id;
    public $score_value;
    public $date_administered;
    public $comments;

    public $subjects = [];
    public $assignmentTypes = [];

    public function showAddScore()
    {
        // ensure form is clean
        $this->resetScoreForm();
        Flux::modal('add-score-modal')->show();
    }

    protected function resetScoreForm()
    {
        $this->reset(['subject_id', 'assignment_type_id', 'score_value', 'date_administered', 'comments']);
    }

    public function saveScore()
    {
        $this->validate([
            'subject_id' => 'required|exists:subjects,id',
            'assignment_type_id' => 'required|exists:assignment_types,id',
            'score_value' => 'required|numeric|min:0',
            'date_administered' => 'nullable|date',
            'comments' => 'nullable|string',
        ]);

        Score::create([
            'student_id' => $this->selectedStudent->id,
            'subject_id' => $this->subject_id,
            'assignment_type_id' => $this->assignment_type_id,
            'score' => $this->score_value,
            'date_administered' => $this->date_administered,
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
            ->timer(3000)
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
                wire:model.debounce.500ms="search"
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
                <div class="text-sm text-zinc-500">{{ $student->grade?->name }}</div>
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
                    Scores for {{ $selectedStudent?->full_name }} – <strong class="text-blue-600">{{ $selectedSubject->name }}</strong>
                @else
                    Subjects for {{ $selectedStudent?->full_name }}
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
                <flux:heading size="lg">Record New Score</flux:heading>
            </div>

            <flux:select label="Subject" wire:model="subject_id">
                <flux:select.option :value="null">Select subject</flux:select.option>
                @foreach($this->subjects as $subject)
                    <flux:select.option :value="$subject->id">{{ $subject->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select label="Assignment Type" wire:model="assignment_type_id">
                <flux:select.option :value="null">Select type</flux:select.option>
                @foreach($this->assignmentTypes as $type)
                    <flux:select.option :value="$type->id">{{ $type->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input label="Score" type="number" wire:model="score_value" min="0" step="0.01" />
            <flux:input label="Date Administered" type="date" wire:model="date_administered" />
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