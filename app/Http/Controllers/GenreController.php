<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GenreController extends Controller
{
    public function create()
    {
        return Inertia::render('Admin/AdminGenre', [
            'is_edit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:genres,name'],
        ]);

        Genre::create($validated);

        return redirect()
            ->route('admin.dashboard', ['section' => 'genres'])
            ->with('success', 'Жанр добавлен.');
    }

    public function edit(Genre $genre)
    {
        return Inertia::render('Admin/AdminGenre', [
            'is_edit' => true,
            'genre' => [
                'id' => $genre->id,
                'name' => $genre->name,
            ],
        ]);
    }

    public function update(Request $request, Genre $genre)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('genres', 'name')->ignore($genre->id)],
        ]);

        $genre->update($validated);

        return redirect()
            ->route('admin.dashboard', ['section' => 'genres'])
            ->with('success', 'Жанр обновлен.');
    }

    public function destroy(Genre $genre)
    {
        $genre->delete();

        return redirect()
            ->route('admin.dashboard', ['section' => 'genres'])
            ->with('success', 'Жанр удален.');
    }
}
