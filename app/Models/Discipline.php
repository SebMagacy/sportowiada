<?php

namespace App\Models;

use Database\Factories\DisciplineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['name', 'alias', 'description'])]

class Discipline extends Model implements HasMedia
{
    /** @use HasFactory<DisciplineFactory> */
    use HasFactory;

    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')
            ->singleFile();
    }

    /** @return HasMany<League, $this> */
    public function leagues(): HasMany
    {
        return $this->hasMany(League::class);
    }
}
