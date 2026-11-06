<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\ContestImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ContestController extends Controller
{
    public function index()
    {
        $contests = Contest::with('images')
            ->latest()
            ->paginate(10);
            
        return view('admin.contests.index', compact('contests'));
    }

    public function create()
    {
        return view('admin.contests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
            'rules' => 'nullable|string',
        ]);

        $contest = Contest::create($validated);

        return redirect()
            ->route('admin.contests.show', $contest)
            ->with('success', 'Contest created successfully!');
    }

    public function show(Contest $contest)
    {
        $contest->load(['images' => function($query) {
            $query->orderBy('display_order');
        }]);
        
        return view('admin.contests.show', compact('contest'));
    }

    public function edit(Contest $contest)
    {
        return view('admin.contests.edit', compact('contest'));
    }

    public function update(Request $request, Contest $contest)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
            'rules' => 'nullable|string',
        ]);

        $contest->update($validated);

        return redirect()
            ->route('admin.contests.show', $contest)
            ->with('success', 'Contest updated successfully!');
    }

    public function destroy(Contest $contest)
    {
        // Delete associated images first
        foreach ($contest->images as $image) {
            Storage::delete('public/' . $image->image_path);
            $image->delete();
        }
        
        $contest->delete();

        return redirect()
            ->route('admin.contests.index')
            ->with('success', 'Contest deleted successfully!');
    }

    public function showUploadForm(Contest $contest)
    {
        return view('admin.contests.upload', compact('contest'));
    }

    public function uploadImages(Request $request, Contest $contest)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
            'titles.*' => 'nullable|string|max:255',
            'descriptions.*' => 'nullable|string',
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                // Store the file
                $path = $file->store("contests/{$contest->id}", 'public');
                
                // Create thumbnail
                $this->createThumbnail(storage_path('app/public/' . $path));
                
                // Save to database
                $contest->images()->create([
                    'image_path' => $path,
                    'title' => $request->titles[$index] ?? null,
                    'description' => $request->descriptions[$index] ?? null,
                    'display_order' => $contest->images()->max('display_order') + 1,
                ]);
            }
        }

        return redirect()
            ->route('admin.contests.show', $contest)
            ->with('success', 'Images uploaded successfully!');
    }

    public function deleteImage(ContestImage $image)
    {
        // Delete the file
        Storage::delete('public/' . $image->image_path);
        
        // Delete the record
        $image->delete();

        return back()->with('success', 'Image deleted successfully!');
    }

    protected function createThumbnail($path, $width = 800, $height = 800)
    {
        $img = Image::make($path);
        $img->resize($width, $height, function($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        $img->save($path);
    }
}
