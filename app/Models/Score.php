<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'student_id',
        'subject_id',
        'grade_id',
        'teacher_id',
        'comments',
        'score',
        'assignment_type_id',
        'date_administered',
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'date_administered' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function assignmentType()
    {
        return $this->belongsTo(AssignmentType::class, 'assignment_type_id');
    }
}