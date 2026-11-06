<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    public function index()
    {
        $ads = Ad::orderBy('display_order')->get();
        return view('admin.ads.index', compact('ads'));
    }

    public function create()
    {
        return view('admin.ads.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'target_url' => 'nullable|url',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0'
        ]);

        $file = $request->file('image');
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('ads', $filename, 'public');

        // Ensure physical file is in public/storage/ads as well
        $publicAdsDir = public_path('storage/ads');
        if (!file_exists($publicAdsDir)) {
            @mkdir($publicAdsDir, 0755, true);
        }
        @copy(storage_path('app/public/' . $path), public_path('storage/' . $path));

        Ad::create([
            'title' => $validated['title'],
            'image_path' => $path,
            'target_url' => $validated['target_url'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'display_order' => $validated['display_order'] ?? 0
        ]);

        return redirect()->route('admin.ads.index')->with('success', 'Ad created successfully!');
    }

    public function edit(Ad $ad)
    {
        return view('admin.ads.edit', compact('ad'));
    }

    public function update(Request $request, Ad $ad)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'target_url' => 'nullable|url',
            'is_active' => 'boolean',
            'display_order' => 'integer|min:0'
        ]);

        $data = [
            'title' => $validated['title'],
            'target_url' => $validated['target_url'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'display_order' => $validated['display_order'] ?? 0
        ];

        if ($request->hasFile('image')) {
            if ($ad->image_path) {
                Storage::disk('public')->delete($ad->image_path);
                @unlink(public_path('storage/' . $ad->image_path));
            }

            $file = $request->file('image');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('ads', $filename, 'public');

            $publicAdsDir = public_path('storage/ads');
            if (!file_exists($publicAdsDir)) {
                @mkdir($publicAdsDir, 0755, true);
            }
            @copy(storage_path('app/public/' . $path), public_path('storage/' . $path));

            $data['image_path'] = $path;
        }

        $ad->update($data);

        return redirect()->route('admin.ads.index')->with('success', 'Ad updated successfully!');
    }

    public function destroy(Ad $ad)
    {
        if ($ad->image_path) {
            Storage::disk('public')->delete($ad->image_path);
            @unlink(public_path('storage/' . $ad->image_path));
        }
        
        $ad->delete();
        return redirect()->route('admin.ads.index')->with('success', 'Ad deleted successfully!');
    }

    /**
     * Toggle the active status of an ad.
     *
     * @param  \App\Models\Ad  $ad
     * @return \Illuminate\Http\Response
     */
    public function toggleStatus(Ad $ad)
    {
        $ad->update([
            'is_active' => !$ad->is_active
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $ad->is_active,
            'message' => 'Ad status updated successfully!'
        ]);
    }
}
