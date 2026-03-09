<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Student extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'firstname',
        'middlename',
        'lastname',
        'dob',
        'contact_number',
        'address',
        'email',
        'grade_id',
    ];

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    public function getFullNameAttribute()
    {
        return $this->firstname . ' ' . ($this->middlename ? $this->middlename . ' ' : '') . $this->lastname;
    }
}
