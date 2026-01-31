<?php

namespace App\Http\Controllers;

use App\Models\Studio;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StudioController extends Controller
{
    public function create()
    {
        return Inertia::render('Admin/AdminStudio', [
            'is_edit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:studios,name'],
        ]);

        $studio = Studio::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $studio->id,
                'name' => $studio->name,
            ], 201);
        }

        return redirect()
            ->route('admin.dashboard', ['section' => 'studios'])
            ->with('success', 'Студия добавлена.');
    }

    public function edit(Studio $studio)
    {
        return Inertia::render('Admin/AdminStudio', [
            'is_edit' => true,
            'studio' => [
                'id' => $studio->id,
                'name' => $studio->name,
            ],
        ]);
    }

    public function update(Request $request, Studio $studio)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('studios', 'name')->ignore($studio->id)],
        ]);

        $studio->update($validated);

        return redirect()
            ->route('admin.dashboard', ['section' => 'studios'])
            ->with('success', 'Студия обновлена.');
    }

    public function destroy(Studio $studio)
    {
        $studio->delete();

        return redirect()
            ->route('admin.dashboard', ['section' => 'studios'])
            ->with('success', 'Студия удалена.');
    }
}
