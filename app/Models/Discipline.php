<?php

namespace App\Models;

use Database\Factories\DisciplineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'alias'])]

class Discipline extends Model
{
    /** @use HasFactory<DisciplineFactory> */
    use HasFactory;
}
