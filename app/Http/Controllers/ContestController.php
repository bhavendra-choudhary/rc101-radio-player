<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContestController extends Controller
{
    /**
     * Display a listing of the contests.
     */
    public function index()
    {
        $contests = Contest::latest()->get();
        return view('admin.contests.index', compact('contests'));
    }

    /**
     * Show the form for creating a new contest.
     */
    public function create()
    {
        return view('admin.contests.create');
    }

    /**
     * Helper to process and save contest images across both public and app storage directories.
     */
    private function processUploadedImages(array $files, Contest $contest, ?string $title = null, ?string $description = null): int
    {
        $destinationPath = public_path('storage/contests/' . $contest->id);
        $appStorageDir = storage_path('app/public/contests/' . $contest->id);

        if (!file_exists($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }
        if (!file_exists($appStorageDir)) {
            @mkdir($appStorageDir, 0755, true);
        }

        $order = ($contest->images()->max('display_order') ?? 0) + 1;
        $savedCount = 0;

        foreach ($files as $image) {
            if (!$image || !$image->isValid()) {
                continue;
            }

            $filename = uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($destinationPath, $filename);

            @copy($destinationPath . '/' . $filename, $appStorageDir . '/' . $filename);

            ContestImage::create([
                'contest_id' => $contest->id,
                'image_path' => 'contests/' . $contest->id . '/' . $filename,
                'title' => $title ?: $contest->title,
                'description' => $description ?: $contest->description,
                'display_order' => $order++,
            ]);

            $savedCount++;
        }

        return $savedCount;
    }

    /**
     * Store a newly created contest in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $contest = Contest::create([
            'title' => $validated['title'],
            'url' => $validated['url'] ?? null,
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
        ]);

        if ($request->hasFile('images')) {
            $this->processUploadedImages($request->file('images'), $contest);
        }

        return redirect()->route('admin.contests.index')
            ->with('success', 'Contest created successfully!');
    }

    /**
     * Display the specified contest.
     */
    public function show(Contest $contest)
    {
        return view('admin.contests.show', compact('contest'));
    }

    /**
     * Show the form for editing the specified contest.
     */
    public function edit(Contest $contest)
    {
        return view('admin.contests.edit', compact('contest'));
    }

    /**
     * Update the specified contest in storage.
     */
    public function update(Request $request, Contest $contest)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $contest->update([
            'title' => $validated['title'],
            'url' => $validated['url'] ?? null,
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : $contest->is_active,
        ]);

        if ($request->hasFile('images')) {
            $this->processUploadedImages($request->file('images'), $contest);
        }

        return redirect()->route('admin.contests.index')
            ->with('success', 'Contest updated successfully!');
    }

    /**
     * Remove the specified contest from storage.
     */
    public function destroy(Contest $contest)
    {
        foreach ($contest->images as $img) {
            @unlink(public_path('storage/' . $img->image_path));
            @unlink(storage_path('app/public/' . $img->image_path));
        }
        $contest->images()->delete();
        $contest->delete();

        return redirect()->route('admin.contests.index')
            ->with('success', 'Contest deleted successfully!');
    }

    /**
     * Show the form for uploading images to a contest.
     */
    public function showUploadForm(Contest $contest)
    {
        return view('admin.contests.upload', compact('contest'));
    }

    /**
     * Store uploaded images for a contest.
     */
    public function uploadImages(Request $request, Contest $contest)
    {
        // Determine the storage source
        $storageSource = $request->input('storage_source', 'local');

        /**
         * LOCAL UPLOAD
         */
        if ($storageSource === 'local') {
            $request->validate([
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
            ]);

            if ($request->hasFile('images')) {
                $this->processUploadedImages(
                    $request->file('images'),
                    $contest,
                    $request->title,
                    $request->description
                );

                return redirect()->route('admin.contests.show', $contest)
                    ->with('success', 'Images uploaded successfully!');
            }

            return back()->with('error', 'No images were uploaded.');
        }

        /**
         * CLOUD UPLOAD
         */
        else {
            $request->validate([
                'cloud_file_data' => 'required',
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
            ]);

            $cloudFiles = json_decode($request->input('cloud_file_data'), true);

            if (empty($cloudFiles)) {
                return back()->with('error', 'No cloud files selected.');
            }

            $uploadedCount = 0;

            foreach ($cloudFiles as $fileData) {
                $source = $fileData['source'] ?? 'Cloud Storage';
                $fileName = $fileData['name'] ?? 'unknown_file.jpg';

                $path = 'contests/' . $contest->id . '/' . time() . '_' . $fileName;

                ContestImage::create([
                    'contest_id' => $contest->id,
                    'image_path' => $path,
                    'title' => $request->title ?: 'Cloud Upload: ' . $fileName,
                    'description' => $request->description ?: 'Uploaded from ' . $source,
                    'display_order' => ($contest->images()->max('display_order') ?? 0) + 1,
                ]);

                $uploadedCount++;
            }

            if ($uploadedCount > 0) {
                return redirect()->route('admin.contests.show', $contest)
                    ->with('success', $uploadedCount . ' image(s) information saved from cloud storage.');
            }

            return back()->with('error', 'Failed to process cloud storage files.');
        }
    }

    /**
     * Delete a contest image.
     */
    public function deleteImage(ContestImage $image)
    {
        try {
            $publicPath = public_path('storage/' . $image->image_path);
            if (file_exists($publicPath)) {
                @unlink($publicPath);
            }
            $storagePath = storage_path('app/public/' . $image->image_path);
            if (file_exists($storagePath)) {
                @unlink($storagePath);
            }
            Storage::disk('public')->delete($image->image_path);
        } catch (\Exception $e) {
            // Ignore error and proceed to delete database record
        }

        $image->delete();

        return back()->with('success', 'Image deleted successfully!');
    }
}
