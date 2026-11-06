<?php

namespace App\Http\Controllers;

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
        $events = CommunityEvent::active()->ordered()->get();
        return view('admin.community_events.index', compact('events'));
    }

    /**
     * Show the form for creating new community event.
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
                'event_date' => 'required|date',
                'location' => 'required|string|max:255',
                'source_url' => 'nullable|url|max:255',
                'status' => 'required|in:active,inactive,cancelled',
                'display_order' => 'integer|min:0',
            ]);

            $eventData = [
                'title' => $validated['title'],
                'description' => $validated['description'],
                'event_date' => $validated['event_date'],
                'location' => $validated['location'],
                'source_url' => $validated['source_url'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'display_order' => $validated['display_order'] ?? 0,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                Log::info('Image upload started for community event');
                
                $file = $request->file('image');
                $destinationPath = public_path('storage/community_events');
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $filename);
                
                $path = 'community_events/' . $filename; // Store relative path compatible with asset() helper
                Log::info('Image stored at: ' . $path);
                
                $eventData['image'] = $path;
            } else {
                Log::info('No image file found in request');
            }

            $event = CommunityEvent::create($eventData);

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
                'event_date' => 'required|date',
                'location' => 'required|string|max:255',
                'source_url' => 'nullable|url|max:255',
                'status' => 'required|in:active,inactive,cancelled',
                'display_order' => 'integer|min:0',
            ]);

            $eventData = [
                'title' => $validated['title'],
                'description' => $validated['description'],
                'event_date' => $validated['event_date'],
                'location' => $validated['location'],
                'source_url' => $validated['source_url'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'display_order' => $validated['display_order'] ?? 0,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($community_event->image) {
                     $oldImagePath = public_path('storage/' . $community_event->image);
                     if (file_exists($oldImagePath)) {
                         unlink($oldImagePath);
                     }
                }

                $file = $request->file('image');
                $destinationPath = public_path('storage/community_events');
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $filename);
                
                $eventData['image'] = 'community_events/' . $filename;
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
            if ($community_event->image) {
                $imagePath = public_path('storage/' . $community_event->image);
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
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
            // Toggle between active and inactive
            $community_event->status = $community_event->status === 'active' ? 'inactive' : 'active';
            $community_event->save();

            return response()->json([
                'success' => true,
                'message' => 'Community event status updated successfully!',
                'status' => $community_event->status
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling community event status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating community event status.'
            ], 500);
        }
    }

    /**
     * Get active community events for the public API.
     */
    public function getCommunityEvents()
    {
        try {
            // Get active events, ordered by display_order and event_date
            $events = CommunityEvent::active()
                ->ordered()
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'event_date' => $event->event_date,
                        'location' => $event->location,
                        'source_url' => $event->source_url,
                        'image' => $event->image ? asset('storage/' . $event->image) : null,
                        'status' => $event->status
                    ];
                });

            return response()->json($events);

        } catch (\Exception $e) {
            Log::error('Error fetching community events: ' . $e->getMessage());
            return response()->json([
                'error' => 'Unable to load events'
            ], 500);
        }
    }
}
