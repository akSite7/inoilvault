<?php

namespace App\Http\Controllers;

use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FriendController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return $this->renderFriends($user, $user->username);
    }

    public function show(Request $request, User $user)
    {
        return $this->renderFriends($user, $user->username);
    }

    private function renderFriends(User $user, string $username)
    {
        $viewer = auth()->user();

        $accepted = FriendRequest::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($user) {
                $query->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->with(['requester:id,username,avatar_path,role', 'addressee:id,username,avatar_path,role'])
            ->latest()
            ->get();

        $friends = $accepted->map(function (FriendRequest $requestRow) use ($user) {
            $friend = $requestRow->requester_id === $user->id ? $requestRow->addressee : $requestRow->requester;
            return [
                'id' => $friend->id,
                'username' => $friend->username,
                'role' => $friend->role ?? 'user',
                'avatar_url' => $friend->avatar_path ? Storage::url($friend->avatar_path) : '/storage/images/placeholders/avatar-placeholder.png',
            ];
        });

        $friendIds = $friends->pluck('id')->filter()->values();
        $lastActivities = $friendIds->isEmpty()
            ? collect()
            : DB::table('sessions')->whereIn('user_id', $friendIds)->pluck('last_activity', 'user_id');
        $nowTs = now()->timestamp;

        $friends = $friends->map(function ($friend) use ($lastActivities, $nowTs) {
            $lastActivity = $lastActivities[$friend['id']] ?? null;
            $friend['is_online'] = $lastActivity && ($nowTs - $lastActivity) < 120;
            return $friend;
        });

        $incoming = FriendRequest::query()
            ->where('status', 'pending')
            ->where('addressee_id', $user->id)
            ->with('requester:id,username,avatar_path,role')
            ->latest()
            ->get()
            ->map(function (FriendRequest $requestRow) {
                return [
                    'id' => $requestRow->id,
                    'user' => [
                        'id' => $requestRow->requester->id,
                        'username' => $requestRow->requester->username,
                        'role' => $requestRow->requester->role ?? 'user',
                        'avatar_url' => $requestRow->requester->avatar_path ? Storage::url($requestRow->requester->avatar_path) : '/storage/images/placeholders/avatar-placeholder.png',
                    ],
                ];
            });

        $outgoing = FriendRequest::query()
            ->where('status', 'pending')
            ->where('requester_id', $user->id)
            ->with('addressee:id,username,avatar_path,role')
            ->latest()
            ->get()
            ->map(function (FriendRequest $requestRow) {
                return [
                    'id' => $requestRow->id,
                    'user' => [
                        'id' => $requestRow->addressee->id,
                        'username' => $requestRow->addressee->username,
                        'role' => $requestRow->addressee->role ?? 'user',
                        'avatar_url' => $requestRow->addressee->avatar_path ? Storage::url($requestRow->addressee->avatar_path) : '/storage/images/placeholders/avatar-placeholder.png',
                    ],
                ];
            });

        return Inertia::render('Profile/ProfileFriends', [
            'friends' => $friends,
            'incoming' => $viewer && $viewer->id === $user->id ? $incoming : [],
            'outgoing' => $viewer && $viewer->id === $user->id ? $outgoing : [],
            'username' => $username,
            'is_owner' => $viewer && $viewer->id === $user->id,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $actor = $request->user();
        if ($actor->id === $user->id) {
            return redirect()->back();
        }

        $existing = FriendRequest::query()
            ->where(function ($query) use ($actor, $user) {
                $query->where('requester_id', $actor->id)
                    ->where('addressee_id', $user->id);
            })
            ->orWhere(function ($query) use ($actor, $user) {
                $query->where('requester_id', $user->id)
                    ->where('addressee_id', $actor->id);
            })
            ->first();

        if ($existing) {
            if ($existing->status === 'pending' && $existing->requester_id === $user->id) {
                $existing->update(['status' => 'accepted']);
            }

            return redirect()->back();
        }

        FriendRequest::create([
            'requester_id' => $actor->id,
            'addressee_id' => $user->id,
            'status' => 'pending',
        ]);

        return redirect()->back();
    }

    public function accept(Request $request, FriendRequest $friendRequest)
    {
        if ($friendRequest->addressee_id !== $request->user()->id) {
            abort(403);
        }

        $friendRequest->update(['status' => 'accepted']);

        return redirect()->back();
    }

    public function decline(Request $request, FriendRequest $friendRequest)
    {
        if ($friendRequest->addressee_id !== $request->user()->id) {
            abort(403);
        }

        $friendRequest->update(['status' => 'declined']);

        return redirect()->back();
    }

    public function cancel(Request $request, FriendRequest $friendRequest)
    {
        if ($friendRequest->requester_id !== $request->user()->id) {
            abort(403);
        }

        $friendRequest->delete();

        return redirect()->back();
    }

    public function remove(Request $request, User $user)
    {
        $viewer = $request->user();

        $relation = FriendRequest::query()
            ->where('status', 'accepted')
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
            $relation->delete();
        }

        return redirect()->back();
    }
}
