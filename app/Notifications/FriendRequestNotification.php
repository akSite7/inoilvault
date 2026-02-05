<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class FriendRequestNotification extends Notification
{
    use Queueable;

    public function __construct(public User $actor)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'friend_request',
            'title' => 'Новая заявка в друзья',
            'message' => $this->actor->username . ' отправил(а) заявку в друзья.',
            'url' => '/friends',
            'actor' => [
                'id' => $this->actor->id,
                'username' => $this->actor->username,
                'avatar_url' => $this->actor->avatar_path
                    ? Storage::url($this->actor->avatar_path)
                    : '/storage/images/placeholders/avatar-placeholder.png',
            ],
        ];
    }
}