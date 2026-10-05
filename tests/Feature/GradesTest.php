<?php

use App\Models\Grade;
use App\Models\Student;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function adminUser(): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'user_confirmed_at' => now(),
    ]);

    $user->assignRole(Role::findOrCreate('admin', 'web'));

    return $user;
}

test('admins can view and manage grades', function () {
    $this->actingAs(adminUser());
    $grade = Grade::create(['name' => 'Grade 1']);

    $this->get(route('grades'))
        ->assertOk()
        ->assertSee('Grade 1');

    Livewire::test('grades.index')
        ->call('edit', $grade->id)
        ->set('name', 'Grade One')
        ->call('update')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('grades', ['id' => $grade->id, 'name' => 'Grade One']);

    Livewire::test('grades.index')->call('delete', $grade->id);

    $this->assertDatabaseMissing('grades', ['id' => $grade->id]);
});

test('non-admin users cannot view grades', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'user_confirmed_at' => now(),
    ]);

    $this->actingAs($user)->get(route('grades'))->assertForbidden();
});

test('only admins can access teacher, grade, and administration pages', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'user_confirmed_at' => now(),
    ]);

    $this->actingAs($user);

    foreach (['teachers.index', 'grades', 'admin'] as $route) {
        $this->get(route($route))->assertForbidden();
    }
});

test('admins can access teacher, grade, and administration pages', function () {
    $this->actingAs(adminUser());

    foreach (['teachers.index', 'grades', 'admin'] as $route) {
        $this->get(route($route))->assertOk();
    }
});

test('grades assigned to students cannot be deleted', function () {
    Livewire::actingAs(adminUser());
    $grade = Grade::create(['name' => 'Grade 1']);
    Student::create([
        'firstname' => 'Sam',
        'lastname' => 'Student',
        'dob' => '2015-01-01',
        'contact_number' => '555-0100',
        'address' => '1 Main Street',
        'email' => 'student@example.com',
        'grade_id' => $grade->id,
        'is_deleted' => false,
        'is_advancing' => true,
    ]);

    Livewire::test('grades.index')->call('delete', $grade->id);

    $this->assertDatabaseHas('grades', ['id' => $grade->id]);
});
