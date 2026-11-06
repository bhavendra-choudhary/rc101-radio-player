<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UploadedNews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UploadedNewsController extends Controller
{
    /**
     * Display a listing of the uploaded news.
     */
    public function index()
    {
        $news = UploadedNews::active()->ordered()->get();
        return view('admin.uploaded_news.index', compact('news'));
    }

    /**
     * Show the form for creating new uploaded news.
     */
    public function create()
    {
        return view('admin.uploaded_news.create');
    }

    /**
     * Store a newly created uploaded news.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'source_url' => 'nullable|url|max:255',
                'source_name' => 'nullable|string|max:100',
                'is_active' => 'boolean',
                'display_order' => 'integer|min:0',
            ]);

            $newsData = [
                'title' => trim($validated['title']),
                'content' => trim($validated['content']),
                'source_url' => $validated['source_url'] ?? null,
                'source_name' => $validated['source_name'] ?? (\App\Models\SiteSetting::get('app_name', config('app.name')) . ' Admin'),
                'is_active' => $validated['is_active'] ?? true,
                'display_order' => $validated['display_order'] ?? 0,
                'published_at' => now(),
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                $file = $request->file('image');

                // Ensure directory exists: public/storage/uploaded_news
                $destinationPath = public_path('storage/uploaded_news');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Generate unique filename
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();

                // Move file to public/storage/uploaded_news
                $file->move($destinationPath, $filename);

                // Duplicate to storage/app/public/uploaded_news
                $appStorageDir = storage_path('app/public/uploaded_news');
                if (!file_exists($appStorageDir)) {
                    @mkdir($appStorageDir, 0755, true);
                }
                @copy($destinationPath . '/' . $filename, $appStorageDir . '/' . $filename);

                // Store relative path in DB
                $newsData['image_path'] = 'uploaded_news/' . $filename;
            }


            $news = UploadedNews::create($newsData);

            return redirect()->route('admin.uploaded_news.index')
                ->with('success', 'News article created successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating uploaded news: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Error creating news article. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified uploaded news.
     */
    public function edit(UploadedNews $uploaded_news)
    {
        return view('admin.uploaded_news.edit', compact('uploaded_news'));
    }

    /**
     * Update the specified uploaded news.
     */
    public function update(Request $request, UploadedNews $uploaded_news)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'source_url' => 'nullable|url|max:255',
                'source_name' => 'nullable|string|max:100',
                'is_active' => 'boolean',
                'display_order' => 'integer|min:0',
                'published_at' => 'nullable|date',
            ]);

            $newsData = [
                'title' => trim($validated['title']),
                'content' => trim($validated['content']),
                'source_url' => $validated['source_url'] ?? null,
                'source_name' => $validated['source_name'] ?? (\App\Models\SiteSetting::get('app_name', config('app.name')) . ' Admin'),
                'is_active' => $validated['is_active'] ?? true,
                'display_order' => $validated['display_order'] ?? 0,
                'published_at' => $validated['published_at'] ?? $uploaded_news->published_at,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($uploaded_news->image_path) {
                    @unlink(public_path('storage/' . $uploaded_news->image_path));
                    @unlink(storage_path('app/public/' . $uploaded_news->image_path));
                }

                $file = $request->file('image');

                // Ensure directory exists: public/storage/uploaded_news
                $destinationPath = public_path('storage/uploaded_news');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Generate unique filename
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();

                // Move file to public/storage/uploaded_news
                $file->move($destinationPath, $filename);

                // Duplicate to storage/app/public/uploaded_news
                $appStorageDir = storage_path('app/public/uploaded_news');
                if (!file_exists($appStorageDir)) {
                    @mkdir($appStorageDir, 0755, true);
                }
                @copy($destinationPath . '/' . $filename, $appStorageDir . '/' . $filename);

                // Store relative path in DB
                $newsData['image_path'] = 'uploaded_news/' . $filename;
            }


            $uploaded_news->update($newsData);

            return redirect()->route('admin.uploaded_news.index')
                ->with('success', 'News article updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating uploaded news: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Error updating news article. Please try again.');
        }
    }

    /**
     * Remove the specified uploaded news.
     */
    public function destroy(UploadedNews $uploaded_news)
    {
        try {
            // Delete image if exists
            if ($uploaded_news->image_path) {
                Storage::disk('public')->delete($uploaded_news->image_path);
            }

            $uploaded_news->delete();

            return redirect()->route('admin.uploaded_news.index')
                ->with('success', 'News article deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting uploaded news: ' . $e->getMessage());
            return back()->with('error', 'Error deleting news article. Please try again.');
        }
    }

    /**
     * Toggle the status of the specified uploaded news.
     */
    public function toggleStatus(UploadedNews $uploaded_news)
    {
        try {
            $uploaded_news->is_active = !$uploaded_news->is_active;
            $uploaded_news->save();

            return response()->json([
                'success' => true,
                'message' => 'News article status updated successfully!',
                'is_active' => $uploaded_news->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling uploaded news status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating news article status.'
            ], 500);
        }
    }
}
