<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'description',
    ];

    public function scores()
    {
        return $this->hasMany(Score::class);
    }
    
}
