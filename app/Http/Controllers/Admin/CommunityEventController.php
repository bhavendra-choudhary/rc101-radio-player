<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CommunityEventController extends Controller
{
    /**
     * Display a listing of the community events.
     */
    public function index()
    {
        $events = CommunityEvent::ordered()->get();
        return view('admin.community_events.index', compact('events'));
    }

    /**
     * Show the form for creating a new community event.
     */
    public function create()
    {
        return view('admin.community_events.create');
    }

    /**
     * Store a newly created community event.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'icon' => 'nullable|string|max:100',
                'link_url' => 'nullable|url|max:255',
                'link_text' => 'nullable|string|max:100',
                'event_date' => 'nullable|date',
                'event_time' => 'nullable',
                'location' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'display_order' => 'integer|min:0',
            ]);

            $eventData = [
                'title' => $validated['title'],
                'description' => $validated['description'],
                'icon' => $validated['icon'] ?? 'fas fa-calendar-check',
                'link_url' => $validated['link_url'] ?? null,
                'link_text' => $validated['link_text'] ?? 'Learn More',
                'event_date' => $validated['event_date'] ?? null,
                'event_time' => $validated['event_time'] ?? null,
                'location' => $validated['location'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'display_order' => $validated['display_order'] ?? 0,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                $file = $request->file('image');

                // Ensure directory exists
                $destinationPath = public_path('storage/community_events');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Generate unique filename
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();

                // Move file to public/storage/community_events
                $file->move($destinationPath, $filename);

                // Store relative path in DB
                $eventData['image_path'] = 'community_events/' . $filename;
            }

            CommunityEvent::create($eventData);

            return redirect()->route('admin.community-events.index')
                ->with('success', 'Community event created successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating community event: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Error creating community event. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified community event.
     */
    public function edit(CommunityEvent $community_event)
    {
        return view('admin.community_events.edit', compact('community_event'));
    }

    /**
     * Update the specified community event.
     */
    public function update(Request $request, CommunityEvent $community_event)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'icon' => 'nullable|string|max:100',
                'link_url' => 'nullable|url|max:255',
                'link_text' => 'nullable|string|max:100',
                'event_date' => 'nullable|date',
                'event_time' => 'nullable',
                'location' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'display_order' => 'integer|min:0',
            ]);

            $eventData = [
                'title' => $validated['title'],
                'description' => $validated['description'],
                'icon' => $validated['icon'] ?? 'fas fa-calendar-check',
                'link_url' => $validated['link_url'] ?? null,
                'link_text' => $validated['link_text'] ?? 'Learn More',
                'event_date' => $validated['event_date'] ?? null,
                'event_time' => $validated['event_time'] ?? null,
                'location' => $validated['location'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'display_order' => $validated['display_order'] ?? 0,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($community_event->image_path && file_exists(public_path('storage/' . $community_event->image_path))) {
                    unlink(public_path('storage/' . $community_event->image_path));
                }

                $file = $request->file('image');

                // Ensure directory exists
                $destinationPath = public_path('storage/community_events');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Generate unique filename
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();

                // Move file to public/storage/community_events
                $file->move($destinationPath, $filename);

                // Store relative path in DB
                $eventData['image_path'] = 'community_events/' . $filename;
            }

            $community_event->update($eventData);

            return redirect()->route('admin.community-events.index')
                ->with('success', 'Community event updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating community event: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Error updating community event. Please try again.');
        }
    }

    /**
     * Remove the specified community event.
     */
    public function destroy(CommunityEvent $community_event)
    {
        try {
            // Delete image if exists
            if ($community_event->image_path && file_exists(public_path('storage/' . $community_event->image_path))) {
                unlink(public_path('storage/' . $community_event->image_path));
            }

            $community_event->delete();

            return redirect()->route('admin.community-events.index')
                ->with('success', 'Community event deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting community event: ' . $e->getMessage());
            return back()->with('error', 'Error deleting community event. Please try again.');
        }
    }

    /**
     * Toggle the status of the specified community event.
     */
    public function toggleStatus(CommunityEvent $community_event)
    {
        try {
            $community_event->is_active = !$community_event->is_active;
            $community_event->save();

            return response()->json([
                'success' => true,
                'message' => 'Community event status updated successfully!',
                'is_active' => $community_event->is_active
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling community event status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating community event status.'
            ], 500);
        }
    }
}
