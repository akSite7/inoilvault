<?php

namespace App\Http\Controllers;

use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SourceController extends Controller
{
    public function create()
    {
        return Inertia::render('Admin/AdminSource', [
            'is_edit' => false,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:sources,name'],
        ]);

        Source::create($validated);

        return redirect()
            ->route('admin.dashboard', ['section' => 'sources'])
            ->with('success', 'Первоисточник добавлен.');
    }

    public function edit(Source $source)
    {
        return Inertia::render('Admin/AdminSource', [
            'is_edit' => true,
            'source' => [
                'id' => $source->id,
                'name' => $source->name,
            ],
        ]);
    }

    public function update(Request $request, Source $source)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sources', 'name')->ignore($source->id)],
        ]);

        $source->update($validated);

        return redirect()
            ->route('admin.dashboard', ['section' => 'sources'])
            ->with('success', 'Первоисточник обновлен.');
    }

    public function destroy(Source $source)
    {
        $source->delete();

        return redirect()
            ->route('admin.dashboard', ['section' => 'sources'])
            ->with('success', 'Первоисточник удален.');
    }
}
