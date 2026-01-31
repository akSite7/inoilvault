<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Comment;
use App\Models\CommentReaction;
use Illuminate\Http\Request;

class AnimeCommentController extends Controller
{
    public function store(Request $request, Anime $anime)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Comment::query()->where('anime_id', $anime->id)->find($validated['parent_id']);
            if (!$parent) {
                abort(404);
            }
        }

        Comment::create([
            'anime_id' => $anime->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
        ]);

        return redirect()->back();
    }

    public function update(Request $request, Anime $anime, Comment $comment)
    {
        if ($comment->anime_id !== $anime->id) {
            abort(404);
        }

        if ($comment->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment->update(['body' => $validated['body']]);

        return redirect()->back();
    }

    public function destroy(Request $request, Anime $anime, Comment $comment)
    {
        if ($comment->anime_id !== $anime->id) {
            abort(404);
        }

        $actor = $request->user();
        $canModerate = $actor && in_array($actor->role, ['admin', 'moderator'], true);
        if (!$canModerate) {
            abort(403);
        }

        $comment->delete();

        return redirect()->back();
    }

    public function react(Request $request, Anime $anime, Comment $comment)
    {
        if ($comment->anime_id !== $anime->id) {
            abort(404);
        }

        $validated = $request->validate([
            'value' => ['required', 'in:1,-1'],
        ]);

        $value = (int) $validated['value'];

        $reaction = CommentReaction::query()
            ->where('comment_id', $comment->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($reaction) {
            if ((int) $reaction->value === $value) {
                $reaction->delete();
            } else {
                $reaction->update(['value' => $value]);
            }
        } else {
            CommentReaction::create([
                'comment_id' => $comment->id,
                'user_id' => $request->user()->id,
                'value' => $value,
            ]);
        }

        return redirect()->back();
    }
}
