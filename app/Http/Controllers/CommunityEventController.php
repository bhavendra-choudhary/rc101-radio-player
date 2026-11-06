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
     * Ensure default 3 events exist in the database if table is empty.
     */
    private function ensureDefaultEventsExist(): void
    {
        try {
            if (CommunityEvent::count() === 0) {
                $defaults = [
                    [
                        'title' => 'Meet & Greet',
                        'description' => 'Meet your favorite country stars and win exclusive merchandise at our meet and greets.',
                        'event_date' => now()->addDays(14)->setTime(18, 0),
                        'location' => 'Main Station Studio',
                        'source_url' => url('/events/meet-and-greet'),
                        'image' => null,
                        'status' => 'active',
                        'display_order' => 1,
                    ],
                    [
                        'title' => 'Studio Tour',
                        'description' => 'Get a behind-the-scenes look at our broadcasting facilities and see how radio magic happens.',
                        'event_date' => now()->addDays(21)->setTime(14, 0),
                        'location' => 'Broadcasting Studio',
                        'source_url' => url('/events/studio-tour'),
                        'image' => null,
                        'status' => 'active',
                        'display_order' => 2,
                    ],
                    [
                        'title' => 'Charity Event',
                        'description' => 'Join us for our annual charity event supporting local causes and making a difference in our community.',
                        'event_date' => now()->addDays(30)->setTime(10, 0),
                        'location' => 'Community Center',
                        'source_url' => url('/events/charity-event'),
                        'image' => null,
                        'status' => 'active',
                        'display_order' => 3,
                    ],
                ];

                foreach ($defaults as $d) {
                    CommunityEvent::create($d);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error auto-seeding default community events: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of the community events.
     */
    public function index()
    {
        $this->ensureDefaultEventsExist();
        $events = CommunityEvent::ordered()->get();
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
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'event_date' => 'required|date',
                'location' => 'required|string|max:255',
                'source_url' => 'nullable|url|max:255',
                'status' => 'required|in:active,inactive,cancelled',
                'display_order' => 'integer|min:0',
            ]);

            $eventData = [
                'title' => trim($validated['title']),
                'description' => trim($validated['description']),
                'event_date' => $validated['event_date'],
                'location' => trim($validated['location']),
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

                // Duplicate to storage/app/public/community_events
                $appStorageDir = storage_path('app/public/community_events');
                if (!file_exists($appStorageDir)) {
                    @mkdir($appStorageDir, 0755, true);
                }
                @copy($destinationPath . '/' . $filename, $appStorageDir . '/' . $filename);
                
                $path = 'community_events/' . $filename;
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
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
                'event_date' => 'required|date',
                'location' => 'required|string|max:255',
                'source_url' => 'nullable|url|max:255',
                'status' => 'required|in:active,inactive,cancelled',
                'display_order' => 'integer|min:0',
            ]);

            $eventData = [
                'title' => trim($validated['title']),
                'description' => trim($validated['description']),
                'event_date' => $validated['event_date'],
                'location' => trim($validated['location']),
                'source_url' => $validated['source_url'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'display_order' => $validated['display_order'] ?? 0,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($community_event->image) {
                     @unlink(public_path('storage/' . $community_event->image));
                     @unlink(storage_path('app/public/' . $community_event->image));
                }

                $file = $request->file('image');
                $destinationPath = public_path('storage/community_events');
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $filename);

                // Duplicate to storage/app/public/community_events
                $appStorageDir = storage_path('app/public/community_events');
                if (!file_exists($appStorageDir)) {
                    @mkdir($appStorageDir, 0755, true);
                }
                @copy($destinationPath . '/' . $filename, $appStorageDir . '/' . $filename);
                
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
                @unlink(public_path('storage/' . $community_event->image));
                @unlink(storage_path('app/public/' . $community_event->image));
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
            $this->ensureDefaultEventsExist();

            // Get active events, ordered by display_order and event_date
            $events = CommunityEvent::active()
                ->ordered()
                ->get()
                ->map(function ($event) {
                    $imageUrl = null;
                    if ($event->image) {
                        if (str_starts_with($event->image, 'http://') || str_starts_with($event->image, 'https://')) {
                            $imageUrl = $event->image;
                        } elseif (str_starts_with($event->image, 'storage/')) {
                            $imageUrl = asset($event->image);
                        } else {
                            $imageUrl = asset('storage/' . ltrim($event->image, '/'));
                        }
                    }

                    return [
                        'id' => $event->id,
                        'title' => $event->title,
                        'description' => $event->description,
                        'event_date' => $event->event_date ? $event->event_date->toIso8601String() : null,
                        'formatted_date' => $event->event_date ? $event->event_date->format('l, F j, Y \a\t g:i A') : null,
                        'short_date' => $event->event_date ? $event->event_date->format('M j, Y') : null,
                        'location' => $event->location,
                        'source_url' => $event->source_url,
                        'image' => $imageUrl,
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
