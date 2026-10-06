<?php

use App\Models\LessonPlan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function lessonPlanUserWithRole(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'user_confirmed_at' => now(),
    ]);

    $user->assignRole(Role::findOrCreate($role, 'web'));

    return $user;
}

function fakeDocx(string $name = 'lesson-plan.docx'): UploadedFile
{
    return UploadedFile::fake()->create(
        $name,
        10,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    );
}

test('teachers can upload docx lesson plans and see their status', function () {
    Storage::fake('local');
    $teacher = lessonPlanUserWithRole('teacher');

    $this->actingAs($teacher)
        ->get(route('lesson-plans.index'))
        ->assertOk()
        ->assertSee('Lesson Plans');

    Livewire::actingAs($teacher)
        ->test('lesson-plans.index')
        ->set('title', 'Fractions')
        ->set('document', fakeDocx())
        ->call('uploadPlan')
        ->assertHasNoErrors()
        ->assertSee('Fractions')
        ->assertSee('Pending');

    $plan = LessonPlan::where('teacher_id', $teacher->ulid)->firstOrFail();
    expect($plan->status)->toBe('pending');
    expect(Storage::disk('local')->exists($plan->original_path))->toBeTrue();
});

test('teachers only see their own lesson plans', function () {
    $teacher = lessonPlanUserWithRole('teacher');
    $otherTeacher = lessonPlanUserWithRole('teacher');

    LessonPlan::create([
        'teacher_id' => $teacher->ulid,
        'title' => 'My Plan',
        'original_path' => 'lesson-plans/my-plan.docx',
        'original_filename' => 'my-plan.docx',
    ]);
    LessonPlan::create([
        'teacher_id' => $otherTeacher->ulid,
        'title' => 'Other Plan',
        'original_path' => 'lesson-plans/other-plan.docx',
        'original_filename' => 'other-plan.docx',
    ]);

    Livewire::actingAs($teacher)
        ->test('lesson-plans.index')
        ->assertSee('My Plan')
        ->assertDontSee('Other Plan');
});

test('admins can upload a revised document and mark a lesson plan done', function () {
    Storage::fake('local');
    $teacher = lessonPlanUserWithRole('teacher');
    $admin = lessonPlanUserWithRole('admin');
    Storage::disk('local')->put('lesson-plans/original.docx', 'original');

    $plan = LessonPlan::create([
        'teacher_id' => $teacher->ulid,
        'title' => 'Fractions',
        'original_path' => 'lesson-plans/original.docx',
        'original_filename' => 'original.docx',
    ]);

    $adminPlans = Livewire::actingAs($admin)
        ->test('lesson-plans.index')
        ->assertSee('Fractions')
        ->assertDontSee(route('lesson-plans.download', [$plan->id, 'current']));

    $adminPlans
        ->call('review', $plan->id)
        ->set('revisedDocument', fakeDocx('fractions-revised.docx'))
        ->call('markDone')
        ->assertHasNoErrors()
        ->assertSee(route('lesson-plans.download', [$plan->id, 'current']));

    $plan->refresh();
    expect($plan->status)->toBe('done')
        ->and($plan->completed_by)->toBe($admin->ulid)
        ->and($plan->revised_path)->not->toBeNull()
        ->and($plan->revised_filename)->toBe('fractions-revised.docx');
    expect(Storage::disk('local')->exists($plan->revised_path))->toBeTrue();

    Livewire::actingAs($teacher)
        ->test('lesson-plans.index')
        ->assertSee('Done')
        ->assertSee('fractions-revised.docx');

    $this->actingAs($teacher)
        ->get(route('lesson-plans.download', [$plan->id, 'current']))
        ->assertDownload('fractions-revised.docx');
});

test('teachers can download their own lesson plans but not another teachers plans', function () {
    Storage::fake('local');
    $teacher = lessonPlanUserWithRole('teacher');
    $otherTeacher = lessonPlanUserWithRole('teacher');
    Storage::disk('local')->put('lesson-plans/mine.docx', 'mine');
    Storage::disk('local')->put('lesson-plans/theirs.docx', 'theirs');

    $ownPlan = LessonPlan::create([
        'teacher_id' => $teacher->ulid,
        'title' => 'Mine',
        'original_path' => 'lesson-plans/mine.docx',
        'original_filename' => 'mine.docx',
    ]);
    $plan = LessonPlan::create([
        'teacher_id' => $otherTeacher->ulid,
        'title' => 'Private',
        'original_path' => 'lesson-plans/theirs.docx',
        'original_filename' => 'theirs.docx',
    ]);

    $this->actingAs($teacher)
        ->get(route('lesson-plans.download', [$ownPlan->id, 'current']))
        ->assertDownload('mine.docx');

    $this->get(route('lesson-plans.download', [$plan->id, 'current']))
        ->assertForbidden();

    $this->actingAs(lessonPlanUserWithRole('admin'))
        ->get(route('lesson-plans.download', [$plan->id, 'original']))
        ->assertDownload('theirs.docx');
});

test('lesson plan uploads only accept docx files', function () {
    Livewire::actingAs(lessonPlanUserWithRole('teacher'))
        ->test('lesson-plans.index')
        ->set('title', 'Fractions')
        ->set('document', UploadedFile::fake()->create('lesson-plan.pdf', 10, 'application/pdf'))
        ->call('uploadPlan')
        ->assertHasErrors(['document']);
});
