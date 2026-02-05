<?php

namespace App\Notifications;

use App\Models\Anime;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CommentReplyNotification extends Notification
{
    use Queueable;

    public function __construct(public User $actor, public Anime $anime, public Comment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $snippet = Str::limit(Str::squish($this->comment->body ?? ''), 35, '...');

        return [
            'type' => 'comment_reply',
            'title' => 'Ответ на комментарий',
            'message' => $this->actor->username . ' ответил(а) на ваш комментарий:' . "\n" . '"' . $snippet . '".',
            'comment_body' => Str::squish($this->comment->body ?? ''),
            'url' => '/anime/' . $this->anime->id . '?comment=' . $this->comment->id,
            'comment_id' => $this->comment->id,
            'parent_id' => $this->comment->parent_id,
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