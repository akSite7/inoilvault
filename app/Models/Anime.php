<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anime extends Model
{
    use HasFactory;

    protected $table = 'anime';

    protected $fillable = [
        'title',
        'alt_title',
        'type',
        'year',
        'genres',
        'episodes',
        'status',
        'source',
        'season_date',
        'studio_id',
        'studios',
        'mpaa_rating',
        'age_rating',
        'duration',
        'description',
        'trailer_url',
        'frames',
        'main_character_id',
        'main_characters',
        'main_voice_actor',
        'related_anime_id',
        'related_type',
        'related_items',
        'cover_path',
    ];

    protected $casts = [
        'season_date' => 'date',
        'frames' => 'array',
        'studios' => 'array',
        'main_characters' => 'array',
        'related_items' => 'array',
    ];

    public function studio()
    {
        return $this->belongsTo(Studio::class);
    }

    public function mainCharacter()
    {
        return $this->belongsTo(Character::class, 'main_character_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function listEntries(): HasMany
    {
        return $this->hasMany(AnimeList::class, 'anime_id');
    }

    public function relatedAnime()
    {
        return $this->belongsTo(Anime::class, 'related_anime_id');
    }
}
