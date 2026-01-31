<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\AnimeList;
use Illuminate\Http\Request;

class AnimeListController extends Controller
{
    private const STATUSES = [
        'watching',
        'completed',
        'on_hold',
        'dropped',
        'planned',
    ];

    public function store(Request $request, Anime $anime)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', self::STATUSES)],
        ]);

        AnimeList::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'anime_id' => $anime->id,
            ],
            [
                'status' => $validated['status'],
            ]
        );

        return redirect()->back();
    }
}
