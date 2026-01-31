<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnimeController;
use App\Http\Controllers\AnimeCommentController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\AnimeListController;
use App\Http\Controllers\UserAnimeListController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\CharacterController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\StudioController;
use App\Http\Controllers\UserAdminController;
use App\Models\Anime;
use App\Models\Character;
use App\Models\Genre;
use App\Models\Source;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

Route::get('/', function () {
    $recentAnime = Anime::query()
        ->latest()
        ->take(10)
        ->get()
        ->map(function (Anime $anime) {
            return [
                'id' => $anime->id,
                'title' => $anime->title,
                'alt_title' => $anime->alt_title,
                'type' => $anime->type,
                'year' => $anime->year,
                'cover_url' => $anime->cover_path ? Storage::url($anime->cover_path) : null,
            ];
        });

    $onlineWindowTs = now()->subSeconds(120)->timestamp;

    $onlineCount = DB::table('sessions')
        ->whereNotNull('user_id')
        ->where('last_activity', '>=', $onlineWindowTs)
        ->distinct()
        ->count('user_id');

    $onlineUsers = DB::query()
        ->fromSub(
            DB::table('sessions')
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', $onlineWindowTs)
                ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
                ->groupBy('user_id'),
            'online_sessions'
        )
        ->join('users', 'users.id', '=', 'online_sessions.user_id')
        ->orderByDesc('online_sessions.last_activity')
        ->limit(12)
        ->get(['users.id', 'users.username', 'users.avatar_path', 'users.role', 'online_sessions.last_activity'])
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role ?? 'user',
                'avatar_url' => $user->avatar_path ? Storage::url($user->avatar_path) : '/images/placeholders/avatar-placeholder.png',
                'last_activity' => (int) $user->last_activity,
            ];
        })
        ->values();

    return Inertia::render('Home', [
        'recentAnime' => $recentAnime,
        'onlineCount' => $onlineCount,
        'onlineUsers' => $onlineUsers,
    ]);
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/verification', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::post('/verification', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::post('/verification/resend', [AuthController::class, 'resendVerificationCode'])->name('verification.resend');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::get('/profile/{username}/settings', [AuthController::class, 'editProfile'])->name('profile.settings');
    Route::post('/profile/{username}/settings', [AuthController::class, 'updateProfile'])->name('profile.settings.update');
    Route::get('/profile/{username}', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/activity/ping', [AuthController::class, 'ping'])->name('activity.ping');
    Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
    Route::get('/profile/{user:username}/friends', [FriendController::class, 'show'])->name('friends.show');
    Route::post('/friends/request/{user}', [FriendController::class, 'store'])->name('friends.request');
    Route::post('/friends/request/{friendRequest}/accept', [FriendController::class, 'accept'])->name('friends.accept');
    Route::post('/friends/request/{friendRequest}/decline', [FriendController::class, 'decline'])->name('friends.decline');
    Route::delete('/friends/request/{friendRequest}', [FriendController::class, 'cancel'])->name('friends.cancel');
    Route::delete('/friends/{user}', [FriendController::class, 'remove'])->name('friends.remove');
    Route::post('/anime/{anime}/list', [AnimeListController::class, 'store'])->name('anime.list.store');
    Route::post('/anime/{anime}/comments', [AnimeCommentController::class, 'store'])->name('anime.comments.store');
    Route::put('/anime/{anime}/comments/{comment}', [AnimeCommentController::class, 'update'])->name('anime.comments.update');
    Route::delete('/anime/{anime}/comments/{comment}', [AnimeCommentController::class, 'destroy'])->name('anime.comments.destroy');
    Route::post('/anime/{anime}/comments/{comment}/react', [AnimeCommentController::class, 'react'])->name('anime.comments.react');
});

Route::middleware(['auth', 'admin.access'])->group(function () {
    Route::get('/admin', function () {
        $items = Anime::query()
            ->latest()
            ->get()
            ->map(function (Anime $anime) {
                return [
                    'id' => $anime->id,
                    'title' => $anime->title,
                    'alt_title' => $anime->alt_title,
                    'type' => $anime->type,
                    'year' => $anime->year,
                    'genres' => $anime->genres,
                    'cover_url' => $anime->cover_path ? Storage::url($anime->cover_path) : null,
                ];
            });

        return Inertia::render('Admin/AdminDashboard', [
            'anime' => $items,
            'genres' => Genre::query()->latest()->get(['id', 'name']),
            'studios' => Studio::query()->latest()->get(['id', 'name']),
            'characters' => Character::query()->latest()->get(['id', 'name', 'voice_actor']),
            'sources' => Source::query()->latest()->get(['id', 'name']),
            'users' => User::query()->latest()->get(['id', 'username', 'email']),
            'initialSection' => request('section', 'anime'),
        ]);
    })->name('admin.dashboard');
    Route::get('/admin/anime', [AnimeController::class, 'create'])->name('anime.admin');
    Route::post('/admin/anime', [AnimeController::class, 'store'])->name('anime.admin.store');
    Route::post('/admin/anime/bulk-delete', [AnimeController::class, 'bulkDestroy'])->name('anime.admin.bulk-delete');
    Route::get('/admin/anime/{anime}/edit', [AnimeController::class, 'edit'])->name('anime.admin.edit');
    Route::put('/admin/anime/{anime}', [AnimeController::class, 'update'])->name('anime.admin.update');
    Route::delete('/admin/anime/{anime}', [AnimeController::class, 'destroy'])->name('anime.admin.destroy');
    Route::get('/admin/genres', [GenreController::class, 'create'])->name('genres.admin');
    Route::post('/admin/genres', [GenreController::class, 'store'])->name('genres.admin.store');
    Route::get('/admin/genres/{genre}/edit', [GenreController::class, 'edit'])->name('genres.admin.edit');
    Route::put('/admin/genres/{genre}', [GenreController::class, 'update'])->name('genres.admin.update');
    Route::delete('/admin/genres/{genre}', [GenreController::class, 'destroy'])->name('genres.admin.destroy');
    Route::get('/admin/studios', [StudioController::class, 'create'])->name('studios.admin');
    Route::post('/admin/studios', [StudioController::class, 'store'])->name('studios.admin.store');
    Route::get('/admin/studios/{studio}/edit', [StudioController::class, 'edit'])->name('studios.admin.edit');
    Route::put('/admin/studios/{studio}', [StudioController::class, 'update'])->name('studios.admin.update');
    Route::delete('/admin/studios/{studio}', [StudioController::class, 'destroy'])->name('studios.admin.destroy');
    Route::get('/admin/characters', [CharacterController::class, 'create'])->name('characters.admin');
    Route::post('/admin/characters', [CharacterController::class, 'store'])->name('characters.admin.store');
    Route::get('/admin/characters/{character}/edit', [CharacterController::class, 'edit'])->name('characters.admin.edit');
    Route::put('/admin/characters/{character}', [CharacterController::class, 'update'])->name('characters.admin.update');
    Route::delete('/admin/characters/{character}', [CharacterController::class, 'destroy'])->name('characters.admin.destroy');
    Route::get('/admin/sources', [SourceController::class, 'create'])->name('sources.admin');
    Route::post('/admin/sources', [SourceController::class, 'store'])->name('sources.admin.store');
    Route::get('/admin/sources/{source}/edit', [SourceController::class, 'edit'])->name('sources.admin.edit');
    Route::put('/admin/sources/{source}', [SourceController::class, 'update'])->name('sources.admin.update');
    Route::delete('/admin/sources/{source}', [SourceController::class, 'destroy'])->name('sources.admin.destroy');
    Route::get('/admin/users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.admin.edit');
    Route::put('/admin/users/{user}', [UserAdminController::class, 'update'])->name('users.admin.update');
    Route::delete('/admin/users/{user}', [UserAdminController::class, 'destroy'])->name('users.admin.destroy');
});

Route::get('/anime', [AnimeController::class, 'index'])->name('anime.index');
Route::get('/anime/search', [AnimeController::class, 'search'])->name('anime.search');
Route::get('/anime/{anime}', [AnimeController::class, 'show'])->name('anime.show');
Route::get('/profile/{username}/anime-list', [UserAnimeListController::class, 'show'])->name('profile.anime.list');

