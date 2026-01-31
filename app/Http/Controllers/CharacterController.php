<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CharacterController extends Controller
{
    public function create()
    {
        return Inertia::render('Admin/AdminCharacter', [
            'is_edit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'voice_actor' => ['nullable', 'string', 'max:255'],
        ]);

        $character = Character::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $character->id,
                'name' => $character->name,
                'voice_actor' => $character->voice_actor,
            ], 201);
        }

        return redirect()
            ->route('admin.dashboard', ['section' => 'characters'])
            ->with('success', 'Персонаж добавлен.');
    }

    public function edit(Character $character)
    {
        return Inertia::render('Admin/AdminCharacter', [
            'is_edit' => true,
            'character' => [
                'id' => $character->id,
                'name' => $character->name,
                'voice_actor' => $character->voice_actor,
            ],
        ]);
    }

    public function update(Request $request, Character $character)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('characters', 'name')->ignore($character->id)],
            'voice_actor' => ['nullable', 'string', 'max:255'],
        ]);

        $character->update($validated);

        return redirect()
            ->route('admin.dashboard', ['section' => 'characters'])
            ->with('success', 'Персонаж обновлен.');
    }

    public function destroy(Character $character)
    {
        $character->delete();

        return redirect()
            ->route('admin.dashboard', ['section' => 'characters'])
            ->with('success', 'Персонаж удален.');
    }
}
