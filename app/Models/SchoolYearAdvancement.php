<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYearAdvancement extends Model
{
    protected $fillable = [
        'advanced_at',
        'advanced_by',
    ];

    protected $casts = [
        'advanced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'advanced_by');
    }
}
