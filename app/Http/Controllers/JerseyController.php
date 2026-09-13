<?php

namespace App\Http\Controllers;

use App\Http\Requests\JerseyStoreRequest;
use App\Http\Requests\JerseyUpdateRequest;
use App\Models\Jersey;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class JerseyController extends Controller
{
    public function index(): Response
    {
        $data = Jersey::query()
            ->latest()
            ->get()
            ->map(fn(Jersey $jersey) => [
                'id'              => $jersey->id,
                'name'            => $jersey->name,
                'description'     => $jersey->description,
                'image'           => $jersey->image_url,
                'price'           => $jersey->price,
                'badge'           => $jersey->badge,
                'primary_color'   => $jersey->primary_color,
                'secondary_color' => $jersey->secondary_color,
                'accent_color'    => $jersey->accent_color,
                'sport'           => $jersey->sport,
                'status'          => $jersey->status,
                'created_at'      => $jersey->created_at,
                'updated_at'      => $jersey->updated_at,
            ]);

        return Inertia::render('Admin/Jersey', [
            'data' => $data,
        ]);
    }

    public function store(JerseyStoreRequest $request)
    {
        $validated = $request->validated();

        $path = $request->file('image')->store('jersey', 'public');

        Jersey::create([
            ...$validated,
            'image'  => $path,
            'status' => 'active',
        ]);

        return redirect()
            ->route('admin.jersey.index')
            ->with('success', 'Jersey template created.');
    }

    public function update(JerseyUpdateRequest $request, Jersey $jersey)
    {
        $validated = $request->validated();
        
        unset($validated['image']);

        if ($request->hasFile('image')) {
            if ($jersey->image) {
                Storage::disk('public')->delete($jersey->image);
            }
            $validated['image'] = $request->file('image')->store('jersey', 'public');
        }

        $jersey->update($validated);

        return redirect()
            ->route('admin.jersey.index')
            ->with('success', 'Jersey template updated.');
    }

    public function destroy(Jersey $jersey)
    {
        if ($jersey->image) {
            Storage::disk('public')->delete($jersey->image);
        }

        $jersey->delete();

        return redirect()
            ->route('admin.jersey.index')
            ->with('success', 'Jersey template deleted.');
    }
}
