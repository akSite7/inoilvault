<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Character;
use App\Models\Comment;
use App\Models\Genre;
use App\Models\Studio;
use App\Models\AnimeList;
use App\Models\Source;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AnimeController extends Controller
{
    public function index(Request $request)
    {
        $query = Anime::query();
        $search = (string) $request->get('q', '');
        $sortField = (string) $request->get('sort', 'created');
        $sortDir = (string) $request->get('dir', 'desc');
        $genres = $request->get('genres', []);
        $type = $request->get('type');
        $status = $request->get('status');
        $yearFrom = $request->get('year_from');
        $yearTo = $request->get('year_to');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('alt_title', 'like', '%' . $search . '%');
            });
        }

        if (is_array($genres) && count($genres) > 0) {
            $query->where(function ($builder) use ($genres) {
                foreach ($genres as $genre) {
                    $value = trim((string) $genre);
                    if ($value === '') {
                        continue;
                    }
                    $builder->where('genres', 'like', '%' . $value . '%');
                }
            });
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($yearFrom !== null && $yearFrom !== '') {
            $query->where('year', '>=', (int) $yearFrom);
        }

        if ($yearTo !== null && $yearTo !== '') {
            $query->where('year', '<=', (int) $yearTo);
        }

        $direction = $sortDir === 'asc' ? 'asc' : 'desc';
        if ($sortField === 'title') {
            $query->orderBy('title', $direction);
        } elseif ($sortField === 'created') {
            $query->orderBy('created_at', $direction);
        } else {
            $query->orderBy('season_date', $direction)->orderBy('created_at', $direction);
        }

        $paginator = $query->paginate(10)->withQueryString();
        $items = $paginator->getCollection()->map(function (Anime $anime) {
            return [
                'id' => $anime->id,
                'title' => $anime->title,
                'alt_title' => $anime->alt_title,
                'type' => $anime->type,
                'year' => $anime->year,
                'season_label' => $this->formatSeasonLabel($anime->season_date),
                'genres' => $anime->genres,
                'description' => $anime->description,
                'cover_url' => $anime->cover_path ? Storage::url($anime->cover_path) : null,
            ];
        })->values();

        return Inertia::render('Anime/AnimeCatalog', [
            'anime' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'genres' => Genre::query()->orderBy('name')->pluck('name')->values(),
            'filters' => [
                'q' => $search,
                'sort' => $sortField,
                'dir' => $direction,
                'genres' => $genres,
                'type' => $type,
                'status' => $status,
                'year_from' => $yearFrom,
                'year_to' => $yearTo,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/AdminAnime', [
            'genres' => Genre::query()->orderBy('name')->get(['id', 'name']),
            'studios' => Studio::query()->orderBy('name')->get(['id', 'name']),
            'characters' => Character::query()->orderBy('name')->get(['id', 'name', 'voice_actor']),
            'animeOptions' => Anime::query()->orderBy('title')->get(['id', 'title']),
            'sources' => Source::query()->orderBy('name')->get(['id', 'name']),
            'is_edit' => false,
        ]);
    }

    public function edit(Anime $anime)
    {
        return Inertia::render('Admin/AdminAnime', [
            'genres' => Genre::query()->orderBy('name')->get(['id', 'name']),
            'studios' => Studio::query()->orderBy('name')->get(['id', 'name']),
            'characters' => Character::query()->orderBy('name')->get(['id', 'name', 'voice_actor']),
            'animeOptions' => Anime::query()->orderBy('title')->get(['id', 'title']),
            'sources' => Source::query()->orderBy('name')->get(['id', 'name']),
            'is_edit' => true,
            'anime' => [
                'id' => $anime->id,
                'title' => $anime->title,
                'alt_title' => $anime->alt_title,
                'type' => $anime->type,
                'genres' => $anime->genres ? array_map('trim', explode(',', $anime->genres)) : [],
                'episodes' => $anime->episodes,
                'status' => $anime->status,
                'source' => $anime->source,
                'season_date' => $anime->season_date?->format('Y-m-d'),
                'year' => $anime->year,
                'studio_id' => $anime->studio_id,
                'studios' => is_array($anime->studios) && count($anime->studios) > 0
                    ? $anime->studios
                    : ($anime->studio_id ? [$anime->studio_id] : []),
                'mpaa_rating' => $anime->mpaa_rating,
                'age_rating' => $anime->age_rating,
                'duration' => $anime->duration,
                'main_character_id' => $anime->main_character_id,
                'main_voice_actor' => $anime->main_voice_actor,
                'main_characters' => $this->resolveMainCharacters($anime),
                'related_items' => $this->resolveRelatedItems($anime),
                'related_anime_id' => $anime->related_anime_id,
                'related_type' => $anime->related_type,
                'description' => $anime->description,
                'trailer_url' => $anime->trailer_url,
                'cover_url' => $anime->cover_path ? Storage::url($anime->cover_path) : null,
                'frame_paths' => $anime->frames ?? [],
                'frames' => collect($anime->frames ?? [])
                    ->map(fn (string $path) => Storage::url($path))
                    ->values()
                    ->all(),
            ],
        ]);
    }

    public function show(Anime $anime)
    {
        $seasonLabel = $this->formatSeasonLabel($anime->season_date);
        $mainCharacters = $this->resolveMainCharacters($anime);
        $mainCharacterNames = collect($mainCharacters)->map(fn ($item) => $item['name'] ?? '')->filter()->values();
        $mainVoiceActors = collect($mainCharacters)->map(fn ($item) => $item['voice_actor'] ?? '')->values();

        $currentUser = auth()->user();
        $currentListStatus = null;
        if ($currentUser) {
            $currentListStatus = AnimeList::query()
                ->where('user_id', $currentUser->id)
                ->where('anime_id', $anime->id)
                ->value('status');
        }

        $canModerate = $currentUser && in_array($currentUser->role, ['admin', 'moderator'], true);
        $comments = Comment::query()
            ->where('anime_id', $anime->id)
            ->with([
                'user:id,username,avatar_path,role',
                'parent.user:id,username',
                'reactions',
            ])
            ->orderByDesc('created_at')
            ->get();

        $commentsByParent = $comments->groupBy('parent_id');

        $collectReplies = function ($parentId) use (&$collectReplies, $commentsByParent) {
            $children = $commentsByParent->get($parentId, collect());
            return $children->flatMap(function (Comment $child) use (&$collectReplies) {
                return collect([$child])->merge($collectReplies($child->id));
            });
        };

        $rootComments = $commentsByParent->get(null);
        if ($rootComments === null) {
            $rootComments = $commentsByParent->get('', collect());
        }

        $comments = $rootComments
            ->map(function (Comment $comment) use ($currentUser, $canModerate, $collectReplies) {
                $avatarUrl = $comment->user?->avatar_path ? Storage::url($comment->user->avatar_path) : '/storage/images/placeholders/avatar-placeholder.png';
                $allReplies = $collectReplies($comment->id);
                $replies = $allReplies->sortBy('created_at')->map(function (Comment $reply) use ($currentUser, $canModerate) {
                    $replyAvatar = $reply->user?->avatar_path ? Storage::url($reply->user->avatar_path) : '/storage/images/placeholders/avatar-placeholder.png';
                    $replyReaction = 0;
                    if ($currentUser) {
                        $replyReaction = (int) ($reply->reactions->firstWhere('user_id', $currentUser->id)?->value ?? 0);
                    }

                    return [
                        'id' => $reply->id,
                        'user_id' => $reply->user_id,
                        'user' => $reply->user?->username ?? 'user',
                        'role' => $reply->user?->role ?? 'user',
                        'avatar_url' => $replyAvatar,
                        'reply_to' => $reply->parent?->user?->username ?? 'user',
                        'created_at' => $reply->created_at?->toISOString(),
                        'text' => $reply->body,
                        'likes' => $reply->reactions->where('value', 1)->count(),
                        'dislikes' => $reply->reactions->where('value', -1)->count(),
                        'user_reaction' => $replyReaction,
                        'can_edit' => $currentUser && $currentUser->id === $reply->user_id,
                        'can_delete' => $canModerate || ($currentUser && $currentUser->id === $reply->user_id),
                    ];
                })->values();

                $commentReaction = 0;
                if ($currentUser) {
                    $commentReaction = (int) ($comment->reactions->firstWhere('user_id', $currentUser->id)?->value ?? 0);
                }

                return [
                    'id' => $comment->id,
                    'user_id' => $comment->user_id,
                    'user' => $comment->user?->username ?? 'user',
                    'role' => $comment->user?->role ?? 'user',
                    'avatar_url' => $avatarUrl,
                    'created_at' => $comment->created_at?->toISOString(),
                    'text' => $comment->body,
                    'likes' => $comment->reactions->where('value', 1)->count(),
                    'dislikes' => $comment->reactions->where('value', -1)->count(),
                    'user_reaction' => $commentReaction,
                    'can_edit' => $currentUser && $currentUser->id === $comment->user_id,
                    'can_delete' => $canModerate || ($currentUser && $currentUser->id === $comment->user_id),
                    'replies' => $replies,
                ];
            })
            ->values();

        return Inertia::render('Anime/AnimeShow', [
            'anime' => [
                'id' => $anime->id,
                'title' => $anime->title,
                'alt_title' => $anime->alt_title,
                'type' => $anime->type,
                'year' => $anime->year,
                'genres' => $anime->genres,
                'episodes' => $anime->episodes,
                'status' => $anime->status,
                'source' => $anime->source,
                'season_label' => $seasonLabel,
                'studio' => $anime->studio?->name,
                'studios' => $this->resolveStudiosForView($anime),
                'mpaa_rating' => $anime->mpaa_rating,
                'age_rating' => $anime->age_rating,
                'duration' => $anime->duration,
                'main_character' => $mainCharacterNames->implode(' | '),
                'main_voice_actor' => $mainVoiceActors->implode(' | '),
                'description' => $anime->description,
                'frames' => collect($anime->frames ?? [])
                    ->map(fn (string $path) => Storage::url($path))
                    ->values()
                    ->all(),
                'trailer_url' => $anime->trailer_url,
                'cover_url' => $anime->cover_path ? Storage::url($anime->cover_path) : null,
                'related_items' => $this->buildRelatedItemsForView($anime),
                'list_status' => $currentListStatus,
            ],
            'comments' => $comments,
            'current_user' => $currentUser
                ? [
                    'id' => $currentUser->id,
                    'username' => $currentUser->username,
                    'avatar_url' => $currentUser->avatar_path ? Storage::url($currentUser->avatar_path) : '/storage/images/placeholders/avatar-placeholder.png',
                ]
                : null,
        ]);
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->get('q', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $items = Anime::query()
            ->where(function ($builder) use ($query) {
                $builder
                    ->where('title', 'like', '%' . $query . '%')
                    ->orWhere('alt_title', 'like', '%' . $query . '%');
            })
            ->orderBy('title')
            ->limit(6)
            ->get()
            ->map(function (Anime $anime) {
                $seasonLabel = $this->formatSeasonLabel($anime->season_date);

                return [
                    'id' => $anime->id,
                    'title' => $anime->title,
                    'alt_title' => $anime->alt_title,
                    'type' => $anime->type,
                    'season_label' => $seasonLabel,
                    'cover_url' => $anime->cover_path ? Storage::url($anime->cover_path) : null,
                ];
            });

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'alt_title' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:60'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['string', 'max:60'],
            'episodes' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:60'],
            'source' => ['nullable', 'string', 'max:60', Rule::exists('sources', 'name')],
            'season_date' => ['required', 'date'],
            'studio_id' => ['nullable', 'exists:studios,id'],
            'studios' => ['nullable', 'array', 'max:5'],
            'studios.*' => ['integer', 'exists:studios,id'],
            'mpaa_rating' => ['nullable', 'string', 'max:30'],
            'age_rating' => ['nullable', 'string', 'max:30'],
            'duration' => ['nullable', 'string', 'max:60'],
            'main_character_id' => ['nullable', 'exists:characters,id'],
            'main_voice_actor' => ['nullable', 'string', 'max:255'],
            'main_characters' => ['nullable', 'array'],
            'main_characters.*.id' => ['nullable', 'integer', 'exists:characters,id'],
            'main_characters.*.name' => ['nullable', 'string', 'max:255'],
            'main_characters.*.voice_actor' => ['nullable', 'string', 'max:255'],
            'related_anime_id' => ['nullable', 'integer', 'exists:anime,id'],
            'related_type' => ['nullable', 'string', 'max:40'],
            'related_items' => ['nullable', 'array'],
            'related_items.*.anime_id' => ['required', 'integer', 'exists:anime,id'],
            'related_items.*.type' => ['required', 'string', 'max:40'],
            'description' => ['nullable', 'string'],
            'trailer_url' => ['nullable', 'string', 'max:255'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'keep_frames' => ['nullable', 'array'],
            'keep_frames.*' => ['string'],
            'frames' => ['nullable', 'array', 'max:20'],
            'frames.*' => ['image', 'max:4096'],
        ]);

        $seasonDate = Carbon::parse($validated['season_date']);
        $year = (int) $seasonDate->format('Y');
        $relatedItems = $this->normalizeRelatedItems($validated['related_items'] ?? []);
        $primaryRelated = $relatedItems[0] ?? null;
        $mainCharacters = $this->normalizeMainCharacters($validated);
        $studios = $this->normalizeStudios($validated);

        $anime = new Anime([
            'title' => $validated['title'],
            'alt_title' => $validated['alt_title'] ?? null,
            'type' => $validated['type'],
            'year' => $year,
            'genres' => implode(', ', $validated['genres']),
            'episodes' => $validated['episodes'] ?? null,
            'status' => $validated['status'] ?? null,
            'source' => $validated['source'] ?? null,
            'season_date' => $seasonDate,
            'studio_id' => $studios[0] ?? ($validated['studio_id'] ?? null),
            'studios' => count($studios) ? $studios : null,
            'mpaa_rating' => $validated['mpaa_rating'] ?? null,
            'age_rating' => $validated['age_rating'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'main_character_id' => $mainCharacters[0]['id'] ?? ($validated['main_character_id'] ?? null),
            'main_voice_actor' => $this->implodeMainVoiceActors($mainCharacters) ?: ($validated['main_voice_actor'] ?? null),
            'main_characters' => $mainCharacters ?: null,
            'related_anime_id' => $primaryRelated['anime_id'] ?? ($validated['related_anime_id'] ?? null),
            'related_type' => $primaryRelated['type'] ?? ($validated['related_type'] ?? null),
            'related_items' => $relatedItems ?: null,
            'description' => $validated['description'] ?? null,
            'trailer_url' => $validated['trailer_url'] ?? null,
        ]);

        if ($request->hasFile('cover')) {
            $anime->cover_path = $request->file('cover')->store('images/anime', 'public');
        }

        $existingFrames = is_array($anime->frames) ? $anime->frames : [];
        $keepFrames = $validated['keep_frames'] ?? [];
        $keepFrames = array_values(array_intersect($existingFrames, $keepFrames));

        $removedFrames = array_values(array_diff($existingFrames, $keepFrames));
        if (count($removedFrames) > 0) {
            foreach ($removedFrames as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $newFrames = [];
        if ($request->hasFile('frames')) {
            foreach ($request->file('frames') as $frame) {
                $newFrames[] = $frame->store('images/anime/frames', 'public');
            }
        }

        $anime->frames = array_values(array_merge($keepFrames, $newFrames));

        $anime->save();
        $this->syncRelatedItems($anime, $relatedItems, []);

        return redirect()
            ->route('admin.dashboard', ['section' => 'anime'])
            ->with('success', 'Р С’Р Р…Р С‘Р СР Вµ Р Т‘Р С•Р В±Р В°Р Р†Р В»Р ВµР Р…Р С•');
    }
    public function update(Request $request, Anime $anime)
    {
        $previousRelated = $this->resolveRelatedItems($anime);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'alt_title' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:60'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['string', 'max:60'],
            'episodes' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:60'],
            'source' => ['nullable', 'string', 'max:60', Rule::exists('sources', 'name')],
            'season_date' => ['required', 'date'],
            'studio_id' => ['nullable', 'exists:studios,id'],
            'studios' => ['nullable', 'array', 'max:5'],
            'studios.*' => ['integer', 'exists:studios,id'],
            'mpaa_rating' => ['nullable', 'string', 'max:30'],
            'age_rating' => ['nullable', 'string', 'max:30'],
            'duration' => ['nullable', 'string', 'max:60'],
            'main_character_id' => ['nullable', 'exists:characters,id'],
            'main_voice_actor' => ['nullable', 'string', 'max:255'],
            'main_characters' => ['nullable', 'array'],
            'main_characters.*.id' => ['nullable', 'integer', 'exists:characters,id'],
            'main_characters.*.name' => ['nullable', 'string', 'max:255'],
            'main_characters.*.voice_actor' => ['nullable', 'string', 'max:255'],
            'related_anime_id' => ['nullable', 'integer', 'exists:anime,id'],
            'related_type' => ['nullable', 'string', 'max:40'],
            'related_items' => ['nullable', 'array'],
            'related_items.*.anime_id' => ['required', 'integer', 'exists:anime,id'],
            'related_items.*.type' => ['required', 'string', 'max:40'],
            'description' => ['nullable', 'string'],
            'trailer_url' => ['nullable', 'string', 'max:255'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'frames' => ['nullable', 'array', 'max:20'],
            'frames.*' => ['image', 'max:4096'],
        ]);

        $seasonDate = Carbon::parse($validated['season_date']);
        $year = (int) $seasonDate->format('Y');
        $relatedItems = $this->normalizeRelatedItems($validated['related_items'] ?? []);
        $primaryRelated = $relatedItems[0] ?? null;
        $mainCharacters = $this->normalizeMainCharacters($validated);
        $studios = $this->normalizeStudios($validated);

        $anime->fill([
            'title' => $validated['title'],
            'alt_title' => $validated['alt_title'] ?? null,
            'type' => $validated['type'],
            'year' => $year,
            'genres' => implode(', ', $validated['genres']),
            'episodes' => $validated['episodes'] ?? null,
            'status' => $validated['status'] ?? null,
            'source' => $validated['source'] ?? null,
            'season_date' => $seasonDate,
            'studio_id' => $studios[0] ?? ($validated['studio_id'] ?? null),
            'studios' => count($studios) ? $studios : null,
            'mpaa_rating' => $validated['mpaa_rating'] ?? null,
            'age_rating' => $validated['age_rating'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'main_character_id' => $mainCharacters[0]['id'] ?? ($validated['main_character_id'] ?? null),
            'main_voice_actor' => $this->implodeMainVoiceActors($mainCharacters) ?: ($validated['main_voice_actor'] ?? null),
            'main_characters' => $mainCharacters ?: null,
            'related_anime_id' => $primaryRelated['anime_id'] ?? ($validated['related_anime_id'] ?? null),
            'related_type' => $primaryRelated['type'] ?? ($validated['related_type'] ?? null),
            'related_items' => $relatedItems ?: null,
            'description' => $validated['description'] ?? null,
            'trailer_url' => $validated['trailer_url'] ?? null,
        ]);

        if ($request->hasFile('cover')) {
            if ($anime->cover_path) {
                Storage::disk('public')->delete($anime->cover_path);
            }
            $anime->cover_path = $request->file('cover')->store('images/anime', 'public');
        }

        if ($request->hasFile('frames')) {
            $paths = [];
            foreach ($request->file('frames') as $frame) {
                $paths[] = $frame->store('images/anime/frames', 'public');
            }
            $anime->frames = $paths;
        }

        $anime->save();
        $this->syncRelatedItems($anime, $relatedItems, $previousRelated);

        return redirect()
            ->route('admin.dashboard', ['section' => 'anime'])
            ->with('success', 'РР·РјРµРЅРµРЅРёСЏ СЃРѕС…СЂР°РЅРµРЅС‹.');
    }

    public function destroy(Anime $anime)
    {
        if ($anime->cover_path) {
            Storage::disk('public')->delete($anime->cover_path);
        }

        if (is_array($anime->frames)) {
            foreach ($anime->frames as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $anime->delete();

        return redirect()
            ->route('admin.dashboard', ['section' => 'anime'])
            ->with('success', 'РђРЅРёРјРµ СѓРґР°Р»РµРЅРѕ.');
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $items = Anime::query()->whereIn('id', $validated['ids'])->get();

        foreach ($items as $anime) {
            if ($anime->cover_path) {
                Storage::disk('public')->delete($anime->cover_path);
            }

            if (is_array($anime->frames)) {
                foreach ($anime->frames as $path) {
                    Storage::disk('public')->delete($path);
                }
            }

            $anime->delete();
        }

        return redirect()
            ->route('admin.dashboard', ['section' => 'anime'])
            ->with('success', 'Готово.');
    }

    private function normalizeRelatedItems(?array $items): array
    {
        $normalized = collect($items ?? [])
            ->values()
            ->map(function ($item, $index) {
                if (!is_array($item)) {
                    return null;
                }

                return [
                    'index' => $index,
                    'anime_id' => isset($item['anime_id']) ? (int) $item['anime_id'] : null,
                    'type' => isset($item['type']) ? (string) $item['type'] : null,
                ];
            })
            ->filter(fn ($item) => $item && $item['anime_id'] && $item['type'])
            ->values()
            ->all();

        usort($normalized, function ($left, $right) {
            $leftWeight = $this->isPrequelType($left['type']) ? 0 : 1;
            $rightWeight = $this->isPrequelType($right['type']) ? 0 : 1;

            if ($leftWeight !== $rightWeight) {
                return $leftWeight <=> $rightWeight;
            }

            return $left['index'] <=> $right['index'];
        });

        return array_values(array_map(
            fn ($item) => ['anime_id' => $item['anime_id'], 'type' => $item['type']],
            $normalized
        ));
    }

    private function resolveMainCharacters(Anime $anime): array
    {
        $items = $anime->main_characters ?? [];
        if (is_array($items) && count($items) > 0) {
            return collect($items)
                ->map(function ($item) {
                    if (!is_array($item)) {
                        return null;
                    }

                    return [
                        'id' => isset($item['id']) ? (int) $item['id'] : null,
                        'name' => isset($item['name']) ? (string) $item['name'] : null,
                        'voice_actor' => isset($item['voice_actor']) ? (string) $item['voice_actor'] : null,
                    ];
                })
                ->filter(fn ($item) => $item && ($item['name'] ?? null))
                ->values()
                ->all();
        }

        if ($anime->main_character_id) {
            return [[
                'id' => $anime->main_character_id,
                'name' => $anime->mainCharacter?->name,
                'voice_actor' => $anime->main_voice_actor,
            ]];
        }

        return [];
    }

    private function normalizeMainCharacters(array $validated): array
    {
        $items = $validated['main_characters'] ?? [];
        $normalized = collect($items)
            ->map(function ($item) {
                if (!is_array($item)) {
                    return null;
                }

                $id = isset($item['id']) ? (int) $item['id'] : null;
                $name = trim((string) ($item['name'] ?? ''));
                $voiceActor = trim((string) ($item['voice_actor'] ?? ''));

                if ($id) {
                    $character = Character::query()->find($id);
                    if (!$character) {
                        return null;
                    }

                    return [
                        'id' => $character->id,
                        'name' => $character->name,
                        'voice_actor' => $voiceActor !== '' ? $voiceActor : ($character->voice_actor ?? ''),
                    ];
                }

                if ($name === '') {
                    return null;
                }

                $character = Character::query()->firstOrCreate(
                    ['name' => $name],
                    ['voice_actor' => $voiceActor !== '' ? $voiceActor : null],
                );

                if ($voiceActor !== '' && $character->voice_actor !== $voiceActor) {
                    $character->voice_actor = $voiceActor;
                    $character->save();
                }

                return [
                    'id' => $character->id,
                    'name' => $character->name,
                    'voice_actor' => $character->voice_actor ?? '',
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (count($normalized) > 0) {
            return $normalized;
        }

        if (!empty($validated['main_character_id'])) {
            $character = Character::query()->find($validated['main_character_id']);
            if ($character) {
                return [[
                    'id' => $character->id,
                    'name' => $character->name,
                    'voice_actor' => $validated['main_voice_actor'] ?? ($character->voice_actor ?? ''),
                ]];
            }
        }

        return [];
    }

    private function implodeMainVoiceActors(array $mainCharacters): ?string
    {
        if (count($mainCharacters) === 0) {
            return null;
        }

        return collect($mainCharacters)
            ->map(fn ($item) => $item['voice_actor'] ?? '')
            ->implode(' | ');
    }

    private function resolveRelatedItems(Anime $anime): array
    {
        $items = $this->normalizeRelatedItems($anime->related_items ?? []);
        if (count($items) > 0) {
            return $items;
        }

        if ($anime->related_anime_id && $anime->related_type) {
            return $this->normalizeRelatedItems([
                [
                    'anime_id' => $anime->related_anime_id,
                    'type' => $anime->related_type,
                ],
            ]);
        }

        return [];
    }

    private function buildRelatedItemsForView(Anime $anime): array
    {
        $items = $this->resolveRelatedItems($anime);
        if (count($items) === 0) {
            return [];
        }

        $relatedIds = collect($items)->pluck('anime_id')->unique()->values();
        $relatedMap = Anime::query()
            ->whereIn('id', $relatedIds)
            ->get()
            ->keyBy('id');

        return collect($items)
            ->map(function ($item) use ($relatedMap) {
                $relatedAnime = $relatedMap->get($item['anime_id']);
                if (!$relatedAnime) {
                    return null;
                }

                return [
                    'id' => $relatedAnime->id,
                    'title' => $relatedAnime->title,
                    'type' => $relatedAnime->type,
                    'year' => $relatedAnime->year,
                    'relation_type' => $item['type'],
                    'cover_url' => $relatedAnime->cover_path ? Storage::url($relatedAnime->cover_path) : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeStudios(array $validated): array
    {
        $items = collect($validated['studios'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->take(5)
            ->all();

        if (count($items) > 0) {
            return $items;
        }

        if (!empty($validated['studio_id'])) {
            return [(int) $validated['studio_id']];
        }

        return [];
    }

    private function resolveStudiosForView(Anime $anime): array
    {
        $ids = [];
        if (is_array($anime->studios) && count($anime->studios) > 0) {
            $ids = collect($anime->studios)->map(fn ($id) => (int) $id)->filter()->values()->all();
        } elseif ($anime->studio_id) {
            $ids = [(int) $anime->studio_id];
        }

        if (count($ids) === 0) {
            return [];
        }

        $names = Studio::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id');

        return collect($ids)
            ->map(fn ($id) => $names[$id] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function isPrequelType(?string $type): bool
    {
        return $type === 'Предыстория';
    }

    private function inverseRelationType(?string $type): string
    {
        $type = trim((string) $type);

        return match ($type) {
            'Предыстория' => 'Продолжение',
            'Продолжение' => 'Предыстория',
            'Альтернативная история' => 'Альтернативная история',
            default => $type,
        };
    }

    private function syncRelatedItems(Anime $anime, array $currentItems, array $previousItems): void
    {
        $currentItems = $this->normalizeRelatedItems($currentItems);
        $previousItems = $this->normalizeRelatedItems($previousItems);

        $currentKeys = collect($currentItems)
            ->map(fn ($item) => $item['anime_id'] . '|' . $item['type'])
            ->values()
            ->all();
        $previousKeys = collect($previousItems)
            ->map(fn ($item) => $item['anime_id'] . '|' . $item['type'])
            ->values()
            ->all();

        $removed = array_diff($previousKeys, $currentKeys);
        $toRemoveByAnime = [];
        foreach ($removed as $key) {
            [$animeId, $type] = explode('|', $key, 2);
            $toRemoveByAnime[(int) $animeId][] = $this->inverseRelationType($type);
        }

        $toAddByAnime = [];
        foreach ($currentItems as $item) {
            if ($item['anime_id'] === $anime->id) {
                continue;
            }
            $toAddByAnime[$item['anime_id']][] = $this->inverseRelationType($item['type']);
        }

        $affectedIds = collect(array_keys($toAddByAnime))
            ->merge(array_keys($toRemoveByAnime))
            ->unique()
            ->values();

        if ($affectedIds->isEmpty()) {
            return;
        }

        $relatedModels = Anime::query()->whereIn('id', $affectedIds)->get()->keyBy('id');

        foreach ($affectedIds as $relatedId) {
            $relatedAnime = $relatedModels->get($relatedId);
            if (!$relatedAnime) {
                continue;
            }

            $relatedItems = $this->normalizeRelatedItems($relatedAnime->related_items ?? []);
            $removeTypes = $toRemoveByAnime[$relatedId] ?? [];
            if ($removeTypes) {
                $relatedItems = array_values(array_filter(
                    $relatedItems,
                    fn ($item) => !($item['anime_id'] === $anime->id && in_array($item['type'], $removeTypes, true))
                ));
            }

            $addTypes = $toAddByAnime[$relatedId] ?? [];
            foreach ($addTypes as $type) {
                $exists = collect($relatedItems)->contains(
                    fn ($item) => $item['anime_id'] === $anime->id && $item['type'] === $type
                );
                if (!$exists) {
                    $relatedItems[] = ['anime_id' => $anime->id, 'type' => $type];
                }
            }

            $relatedAnime->related_items = $relatedItems ?: null;
            $relatedAnime->related_anime_id = $relatedItems[0]['anime_id'] ?? null;
            $relatedAnime->related_type = $relatedItems[0]['type'] ?? null;
            $relatedAnime->save();
        }
    }

    private function formatSeasonLabel($seasonDate): ?string
    {
        if (!$seasonDate) {
            return null;
        }

        $month = (int) $seasonDate->format('n');
        $year = $seasonDate->format('Y');
        $seasonName = match (true) {
            $month === 12 || $month <= 2 => 'Зима',
            $month >= 3 && $month <= 5 => 'Весна',
            $month >= 6 && $month <= 8 => 'Лето',
            default => 'Осень',
        };

        return $seasonName . ' ' . $year;
    }
}

