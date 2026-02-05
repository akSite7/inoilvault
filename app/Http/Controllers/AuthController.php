<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AnimeList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Models\FriendRequest;
use App\Mail\EmailVerificationCode;

class AuthController extends Controller
{
    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'alpha_dash:ascii', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $verificationCode = (string) random_int(100000, 999999);

        $user = User::create([
            'name' => $validated['username'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'email_verification_code' => Hash::make($verificationCode),
            'email_verification_expires_at' => now()->addMinutes(30),
        ]);

        Mail::to($user->email)->send(new EmailVerificationCode($verificationCode));

        $request->session()->put('verification_email', $user->email);

        return redirect()
            ->route('verification.notice')
            ->with([
                'success' => 'Код подтверждения отправлен на почту.',
                'success_id' => (string) Str::uuid(),
            ]);
    }

    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function showVerifyEmail(Request $request)
    {
        return Inertia::render('Auth/Verification');
    }

    public function verifyEmail(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $email = $request->session()->get('verification_email');
        if (!$email) {
            return back()->withErrors([
                'code' => 'Не удалось определить почту. Запросите новый код.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Пользователь с такой почтой не найден.',
            ]);
        }

        if ($user->email_verified_at) {
            return back()->withErrors([
                'email' => 'Почта уже подтверждена. Войдите в аккаунт.',
            ]);
        }

        if (!$user->email_verification_code || !$user->email_verification_expires_at) {
            return back()->withErrors([
                'code' => 'Код подтверждения не найден. Отправьте новый.',
            ]);
        }

        if (now()->greaterThan($user->email_verification_expires_at)) {
            return back()->withErrors([
                'code' => 'Срок действия кода истек. Отправьте новый.',
            ]);
        }

        if (!Hash::check($validated['code'], $user->email_verification_code)) {
            return back()->withErrors([
                'code' => 'Неверный код.',
            ]);
        }

        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_expires_at = null;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('session_timeout_seconds', 60 * 60 * 24);
        $request->session()->put('last_activity_ts', now()->timestamp);

        return redirect()->route('profile.show', ['username' => $user->username]);
    }

    public function resendVerificationCode(Request $request)
    {
        $email = $request->session()->get('verification_email');
        if (!$email) {
            return back()->withErrors([
                'code' => 'Не удалось определить почту. Запросите новый код.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Пользователь с такой почтой не найден.',
            ]);
        }

        if ($user->email_verified_at) {
            return back()->withErrors([
                'email' => 'Почта уже подтверждена. Войдите в аккаунт.',
            ]);
        }

        $verificationCode = (string) random_int(100000, 999999);
        $user->email_verification_code = Hash::make($verificationCode);
        $user->email_verification_expires_at = now()->addMinutes(30);
        $user->save();

        Mail::to($user->email)->send(new EmailVerificationCode($verificationCode));

        return back()->with([
            'success' => 'Новый код отправлен на почту.',
            'success_id' => (string) Str::uuid(),
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $validated['login'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $login)->first();

        if ($user && !$user->email_verified_at) {
            $request->session()->put('verification_email', $user->email);
            return redirect()->route('verification.notice');
        }

        if (Auth::attempt([$field => $login, 'password' => $validated['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $timeout = $request->boolean('remember') ? 60 * 60 * 24 * 90 : 60 * 60 * 24;
            $request->session()->put('session_timeout_seconds', $timeout);
            $request->session()->put('last_activity_ts', now()->timestamp);

            return redirect()->route('profile.show', ['username' => $request->user()->username]);
        }

        return back()->withErrors([
            'login' => 'Неверный логин или пароль.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function ping(Request $request)
    {
        if (!$request->user()) {
            return response()->noContent();
        }

        $request->session()->put('last_activity_ts', now()->timestamp);

        return response()->noContent();
    }

    public function profile(Request $request)
    {
        return redirect()->route('profile.show', [
            'username' => $request->user()->username,
        ]);
    }

    public function showProfile(Request $request, string $username)
    {
        $user = User::whereRaw('LOWER(username) = ?', [Str::lower($username)])->firstOrFail();
        $lastActivity = DB::table('sessions')
            ->where('user_id', $user->id)
            ->max('last_activity');
        $lastActivity ??= $user->created_at?->timestamp;
        $viewer = $request->user();
        $forceOffline = false;

        $friendStatus = 'none';
        $friendRequestId = null;
        if ($viewer && $viewer->id !== $user->id) {
            $relation = FriendRequest::query()
                ->where(function ($query) use ($viewer, $user) {
                    $query->where('requester_id', $viewer->id)
                        ->where('addressee_id', $user->id);
                })
                ->orWhere(function ($query) use ($viewer, $user) {
                    $query->where('requester_id', $user->id)
                        ->where('addressee_id', $viewer->id);
                })
                ->first();

            if ($relation) {
                $friendRequestId = $relation->id;
                if ($relation->status === 'accepted') {
                    $friendStatus = 'friends';
                } elseif ($relation->status === 'pending' && $relation->requester_id === $viewer->id) {
                    $friendStatus = 'outgoing';
                } elseif ($relation->status === 'pending') {
                    $friendStatus = 'incoming';
                }
            }
        }

        $friendsPreview = FriendRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->with(['requester:id,username,avatar_path,role', 'addressee:id,username,avatar_path,role'])
            ->latest()
            ->get()
            ->map(function (FriendRequest $row) use ($user) {
                $friend = $row->requester_id === $user->id ? $row->addressee : $row->requester;
                return [
                    'id' => $friend->id,
                    'username' => $friend->username,
                    'role' => $friend->role ?? 'user',
                    'avatar_url' => $friend->avatar_path ? Storage::url($friend->avatar_path) : '/storage/images/placeholders/avatar-placeholder.png',
                ];
            })
            ->take(10)
            ->values();

        $friendIds = $friendsPreview->pluck('id')->filter()->values();
        $lastActivities = $friendIds->isEmpty()
            ? collect()
            : DB::table('sessions')
                ->whereIn('user_id', $friendIds)
                ->pluck('last_activity', 'user_id');
        $nowTs = now()->timestamp;

        $friendsPreview = $friendsPreview->map(function ($friend) use ($lastActivities, $nowTs) {
            $lastActivity = $lastActivities[$friend['id']] ?? null;
            $friend['is_online'] = $lastActivity && ($nowTs - $lastActivity) < 120;
            return $friend;
        });

        $listEntries = AnimeList::query()
            ->where('user_id', $user->id)
            ->with('anime:id,episodes')
            ->get();

        $animeCounts = [
            'total' => $listEntries->count(),
            'episodes' => (int) $listEntries
                ->where('status', 'completed')
                ->sum(function (AnimeList $entry) {
                    return (int) ($entry->anime?->episodes ?? 0);
                }),
            'watching' => $listEntries->where('status', 'watching')->count(),
            'completed' => $listEntries->where('status', 'completed')->count(),
            'on_hold' => $listEntries->where('status', 'on_hold')->count(),
            'dropped' => $listEntries->where('status', 'dropped')->count(),
            'planned' => $listEntries->where('status', 'planned')->count(),
        ];

        return Inertia::render('Profile/Profile', [
              'user' => [
                  'id' => $user->id,
                  'name' => $user->name,
                  'username' => $user->username,
                  'email' => $user->email,
                  'role' => $user->role ?? 'user',
                  'avatar_url' => $user->avatar_path ? Storage::url($user->avatar_path) : null,
                  'cover_url' => $user->cover_path ? Storage::url($user->cover_path) : null,
                'bio' => $user->bio,
            ],
            'status' => $this->formatStatus($lastActivity, $forceOffline),
            'last_activity' => $lastActivity,
            'force_offline' => $forceOffline,
            'friend_status' => $friendStatus,
            'friend_request_id' => $friendRequestId,
            'friends_preview' => $friendsPreview->take(10)->values(),
            'friends_count' => $friendsPreview->count(),
            'anime_counts' => $animeCounts,
        ]);
    }

    public function editProfile(Request $request, string $username)
    {
        $user = $request->user();
        if (!$user || Str::lower($user->username) !== Str::lower($username)) {
            abort(403);
        }

        return Inertia::render('Profile/ProfileSettings', [
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'avatar_url' => $user->avatar_path ? Storage::url($user->avatar_path) : null,
                'cover_url' => $user->cover_path ? Storage::url($user->cover_path) : null,
                'bio' => $user->bio,
            ],
        ]);
    }

    public function updateProfile(Request $request, string $username)
    {
        $user = $request->user();
        if (!$user || Str::lower($user->username) !== Str::lower($username)) {
            abort(403);
        }

        $validated = $request->validate([
            'avatar' => ['nullable', 'image', 'max:2048'],
            'cover' => ['nullable', 'image', 'max:4096'],
            'bio' => ['nullable', 'string', 'max:500'],
            'username' => [
                'required',
                'string',
                'max:24',
                'alpha_dash:ascii',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $request->file('avatar')->store('images/avatars', 'public');
        }

        if ($request->hasFile('cover')) {
            if ($user->cover_path) {
                Storage::disk('public')->delete($user->cover_path);
            }

            $user->cover_path = $request->file('cover')->store('images/covers', 'public');
        }

        if (array_key_exists('username', $validated)) {
            $user->username = $validated['username'];
            $user->name = $validated['username'];
        }

        if (array_key_exists('bio', $validated)) {
            $user->bio = $validated['bio'];
        }

        $user->save();

        return redirect()->route('profile.show', [
            'username' => $user->username,
        ])->with([
            'success' => 'Изменения сохранены.',
            'success_id' => (string) Str::uuid(),
        ]);
    }

    private function formatStatus(?int $lastActivity, bool $forceOffline = false): array
    {
        if (!$lastActivity) {
            $lastActivity = now()->subYears(5)->timestamp;
        }

        $lastSeen = now()->createFromTimestamp($lastActivity);
        $minutes = now()->diffInMinutes($lastSeen);

        if ($minutes < 2 && !$forceOffline) {
            return [
                'label' => 'Онлайн',
                'detail' => 'сейчас на сайте',
            ];
        }

        if ($minutes < 60) {
            return [
                'label' => 'Офлайн',
                'detail' => 'Был(а) в сети ' . $minutes . ' ' . $this->formatMinutes($minutes) . ' назад',
            ];
        }

        $hours = now()->diffInHours($lastSeen);
        if ($hours < 24) {
            return [
                'label' => 'Офлайн',
                'detail' => 'Был(а) в сети ' . $hours . ' ' . $this->formatHours($hours) . ' назад',
            ];
        }

        $days = now()->diffInDays($lastSeen);
        if ($days < 7) {
            return [
                'label' => 'Офлайн',
                'detail' => 'Был(а) в сети ' . $days . ' ' . $this->formatDays($days) . ' назад',
            ];
        }

        $weeks = (int) floor($days / 7);
        if ($weeks < 5) {
            return [
                'label' => 'Офлайн',
                'detail' => 'Был(а) в сети ' . $weeks . ' ' . $this->formatWeeks($weeks) . ' назад',
            ];
        }

        $months = (int) floor($days / 30);
        if ($months < 12) {
            return [
                'label' => 'Офлайн',
                'detail' => 'Был(а) в сети ' . $months . ' ' . $this->formatMonths($months) . ' назад',
            ];
        }

        $years = (int) floor($days / 365);
        return [
            'label' => 'Офлайн',
            'detail' => 'Был(а) в сети ' . $years . ' ' . $this->formatYears($years) . ' назад',
        ];
    }

    private function formatMinutes(int $minutes): string
    {
        $mod100 = $minutes % 100;
        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'минут';
        }

        $mod10 = $minutes % 10;
        return match ($mod10) {
            1 => 'минуту',
            2, 3, 4 => 'минуты',
            default => 'минут',
        };
    }

    private function formatHours(int $hours): string
    {
        $mod100 = $hours % 100;
        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'часов';
        }

        $mod10 = $hours % 10;
        return match ($mod10) {
            1 => 'час',
            2, 3, 4 => 'часа',
            default => 'часов',
        };
    }

    private function formatDays(int $days): string
    {
        $mod100 = $days % 100;
        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'дней';
        }

        $mod10 = $days % 10;
        return match ($mod10) {
            1 => 'день',
            2, 3, 4 => 'дня',
            default => 'дней',
        };
    }

    private function formatWeeks(int $weeks): string
    {
        $mod100 = $weeks % 100;
        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'недель';
        }

        $mod10 = $weeks % 10;
        return match ($mod10) {
            1 => 'неделю',
            2, 3, 4 => 'недели',
            default => 'недель',
        };
    }

    private function formatMonths(int $months): string
    {
        $mod100 = $months % 100;
        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'месяцев';
        }

        $mod10 = $months % 10;
        return match ($mod10) {
            1 => 'месяц',
            2, 3, 4 => 'месяца',
            default => 'месяцев',
        };
    }

    private function formatYears(int $years): string
    {
        $mod100 = $years % 100;
        if ($mod100 >= 11 && $mod100 <= 14) {
            return 'лет';
        }

        $mod10 = $years % 10;
        return match ($mod10) {
            1 => 'год',
            2, 3, 4 => 'года',
            default => 'лет',
        };
    }
}

