<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserAdminController extends Controller
{
    public function edit(User $user)
    {
        return Inertia::render('Admin/AdminUserEdit', [
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role ?? 'user',
            ],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:24',
                'alpha_dash:ascii',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['user', 'moderator', 'admin'])],
        ]);

        $actor = $request->user();
        $canModerate = $actor && in_array($actor->role, ['admin', 'moderator'], true);
        if (!$canModerate) {
            if (!$actor || $actor->id !== $user->id) {
                abort(403);
            }
            $validated['username'] = $user->username;
            $validated['email'] = $user->email;
            $validated['password'] = null;
        }

        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.dashboard', ['section' => 'users'])
            ->with('success', 'Пользователь обновлен.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('admin.dashboard', ['section' => 'users'])
            ->with('success', 'Пользователь удален.');
    }
}
