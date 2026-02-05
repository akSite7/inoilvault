<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = (int) $request->query('per_page', 30);
        if ($perPage < 1) {
            $perPage = 1;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $notifications = $user->notifications()
            ->latest()
            ->paginate($perPage);

        $actorIds = $notifications->getCollection()
            ->map(function ($notification) {
                $data = $notification->data ?? [];
                $actor = $data['actor'] ?? [];
                return $actor['id'] ?? null;
            })
            ->filter()
            ->unique()
            ->values();

        $actorMap = collect();
        if ($actorIds->isNotEmpty()) {
            $actorMap = User::query()
                ->whereIn('id', $actorIds)
                ->get(['id', 'username', 'avatar_path'])
                ->keyBy('id');
        }

        $commentIds = $notifications->getCollection()
            ->map(function ($notification) {
                $data = $notification->data ?? [];
                return $data['comment_id'] ?? null;
            })
            ->filter()
            ->unique()
            ->values();

        $commentMap = collect();
        if ($commentIds->isNotEmpty()) {
            $commentMap = Comment::query()
                ->whereIn('id', $commentIds)
                ->get(['id', 'body'])
                ->keyBy('id');
        }

        $items = $notifications
            ->getCollection()
            ->map(function ($notification) use ($actorMap, $commentMap) {
                return $this->formatNotification($notification, $actorMap, $commentMap);
            })
            ->values();

        $pagination = [
            'current_page' => $notifications->currentPage(),
            'last_page' => $notifications->lastPage(),
            'has_more' => $notifications->hasMorePages(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
        ];

        $payload = [
            'items' => $items,
            'pagination' => $pagination,
            'unread' => $user->unreadNotifications()->count(),
        ];

        $isInertia = (bool) $request->header('X-Inertia');
        if (!$isInertia && ($request->wantsJson() || $request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest')) {
            return response()->json($payload);
        }

        return Inertia::render('Notifications/NotificationsIndex', [
            'notifications' => $items,
            'pagination' => $pagination,
            'unread' => $payload['unread'],
        ]);
    }

    public function markRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'unread' => 0,
        ]);
    }

    public function destroy(Request $request, string $notification)
    {
        $user = $request->user();
        $item = $user->notifications()->whereKey($notification)->firstOrFail();
        $item->delete();

        return response()->json([
            'deleted' => $notification,
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    private function formatNotification($notification, $actorMap, $commentMap): array
    {
        $data = $notification->data ?? [];
        $actor = $data['actor'] ?? null;

        if ($actor && !empty($actor['id']) && $actorMap instanceof \Illuminate\Support\Collection) {
            $freshActor = $actorMap->get($actor['id']);
            if ($freshActor) {
                $actor['username'] = $freshActor->username;
                $actor['avatar_url'] = $freshActor->avatar_path
                    ? Storage::url($freshActor->avatar_path)
                    : '/storage/images/placeholders/avatar-placeholder.png';
            }
        }

        $commentBody = $data['comment_body'] ?? null;
        $commentId = $data['comment_id'] ?? null;
        if (!$commentId && !empty($data['url'])) {
            $parts = parse_url($data['url']);
            if (!empty($parts['query'])) {
                parse_str($parts['query'], $query);
                $commentId = $query['comment'] ?? null;
            }
        }
        if (!$commentBody && $commentId && $commentMap instanceof \Illuminate\Support\Collection) {
            $comment = $commentMap->get($commentId);
            if ($comment) {
                $commentBody = Str::squish($comment->body ?? '');
            }
        }

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? null,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'url' => $data['url'] ?? null,
            'actor' => $actor,
            'comment_id' => $commentId,
            'parent_id' => $data['parent_id'] ?? null,
            'comment_body' => $commentBody,
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }
}