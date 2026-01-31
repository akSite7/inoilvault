<?php

namespace App\Http\Controllers;

use App\Models\AnimeList;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserAnimeListController extends Controller
{
    private const STATUS_LABELS = [
        'watching' => 'Смотрю',
        'completed' => 'Просмотрено',
        'on_hold' => 'Отложено',
        'dropped' => 'Брошено',
        'planned' => 'Запланировано',
    ];

    public function show(Request $request, string $username)
    {
        $user = User::query()->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])->firstOrFail();

        $entries = AnimeList::query()
            ->where('user_id', $user->id)
            ->with('anime')
            ->latest()
            ->get()
            ->map(function (AnimeList $entry) {
                return [
                    'id' => $entry->id,
                    'status' => $entry->status,
                    'status_label' => self::STATUS_LABELS[$entry->status] ?? $entry->status,
                    'anime' => [
                        'id' => $entry->anime->id,
                        'title' => $entry->anime->title,
                        'alt_title' => $entry->anime->alt_title,
                        'type' => $entry->anime->type,
                        'episodes' => $entry->anime->episodes,
                        'cover_url' => $entry->anime->cover_path ? Storage::url($entry->anime->cover_path) : null,
                    ],
                ];
            });

        $counts = collect(self::STATUS_LABELS)->mapWithKeys(function ($label, $key) use ($entries) {
            return [$key => $entries->where('status', $key)->count()];
        });

        $friendsPreview = FriendRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->with(['requester:id,username,avatar_path', 'addressee:id,username,avatar_path'])
            ->latest()
            ->get()
            ->map(function (FriendRequest $row) use ($user) {
                $friend = $row->requester_id === $user->id ? $row->addressee : $row->requester;
                return [
                    'id' => $friend->id,
                    'username' => $friend->username,
                    'avatar_url' => $friend->avatar_path ? Storage::url($friend->avatar_path) : '/images/placeholders/avatar-placeholder.png',
                ];
            });

        return Inertia::render('Profile/ProfileAnimeList', [
            'owner' => [
                'id' => $user->id,
                'username' => $user->username,
            ],
            'entries' => $entries,
            'counts' => $counts,
            'friends' => $friendsPreview->take(4)->values(),
            'friends_count' => $friendsPreview->count(),
        ]);
    }
}
