<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Api;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard
     */
    public function index()
    {
        // Debug: Log that we're in the AdminController
        \Log::info('AdminController::index called');
        
        $recentContests = \App\Models\Contest::with('images')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
            
        $activeContests = \App\Models\Contest::where('is_active', true)
            ->where('end_date', '>=', now())
            ->count();
            
        $totalContests = \App\Models\Contest::count();
        $totalImages = \App\Models\ContestImage::count();
        
        // Debug: Log the view we're returning
        \Log::info('Returning admin.dashboard view');
        
        return view('admin.dashboard', [
            'recentContests' => $recentContests,
            'activeContests' => $activeContests,
            'totalContests' => $totalContests,
            'totalImages' => $totalImages
        ]);
    }
    
    /**
     * Show the API management page
     */
    public function apis(Request $request)
    {
        $apis = Api::orderBy('name')->get();
        
        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            // Include masked values for display while keeping actual values hidden
            $apisWithMaskedValues = $apis->map(function ($api) {
                return [
                    'id' => $api->id,
                    'name' => $api->name,
                    'type' => $api->type,
                    'description' => $api->description,
                    'is_active' => $api->is_active,
                    'updated_at' => $api->updated_at,
                    'masked_value' => $api->masked_value
                ];
            });
            return response()->json($apisWithMaskedValues);
        }
        
        return view('admin.apis', compact('apis'));
    }

    /**
     * Store or update an API
     */
    public function storeApi(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'value' => [
                'required',
                'string',
                'min:1',
                function ($attribute, $value, $fail) {
                    if (empty(trim($value))) {
                        $fail('The API value cannot be empty or contain only whitespace.');
                    }
                    if (strlen(trim($value)) < 10) {
                        $fail('The API value must be at least 10 characters long.');
                    }
                },
            ],
            'type' => 'required|string|in:api_key,endpoint,token,secret',
            'description' => 'nullable|string|max:500'
        ]);

        try {
            Api::setValue(
                $request->name,
                $request->value,
                $request->type,
                $request->description
            );

            // Handle AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'API saved successfully!']);
            }

            return redirect()->route('admin.apis')->with('success', 'API saved successfully!');
        } catch (\InvalidArgumentException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()
                ->withInput()
                ->withErrors(['value' => $e->getMessage()]);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to save API: ' . $e->getMessage()], 500);
            }
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save API: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete an API
     */
    public function deleteApi(Request $request, $id)
    {
        $api = Api::findOrFail($id);
        $api->delete();

        // Handle AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'API deleted successfully!']);
        }

        return redirect()->route('admin.apis')->with('success', 'API deleted successfully!');
    }

    /**
     * Toggle API active status
     */
    public function toggleApi(Request $request, $id)
    {
        $api = Api::findOrFail($id);
        $api->is_active = !$api->is_active;
        $api->save();

        $status = $api->is_active ? 'activated' : 'deactivated';
        
        // Clear cache for this API to ensure changes take effect immediately
        $this->clearApiCache($api->name);
        
        \Log::info("API {$api->name} {$status}", [
            'api_id' => $api->id,
            'is_active' => $api->is_active
        ]);
        
        // Handle AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true, 
                'message' => "API {$status} successfully!",
                'is_active' => $api->is_active
            ]);
        }

        return redirect()->route('admin.apis')->with('success', "API {$status} successfully!");
    }
    
    /**
     * Clear cache for specific API
     */
    private function clearApiCache($apiName)
    {
        try {
            // Clear cache based on API type
            switch ($apiName) {
                case 'news_api':
                    // Clear news cache
                    \Cache::forget('news_articles');
                    \Cache::forget('entertainment_news');
                    \Log::info('Cleared news API cache');
                    break;
                    
                case 'ticketmaster_api':
                    // Clear all concert-related caches
                    \Cache::flush(); // Clear all cache for concerts as they have dynamic keys
                    \Log::info('Cleared Ticketmaster API cache');
                    break;
                    
                case 'bandsintown_api':
                case 'spotify_api':
                case 'youtube_api':
                case 'lastfm_api':
                    // Clear general cache for these APIs
                    \Cache::flush();
                    \Log::info("Cleared {$apiName} cache");
                    break;
                    
                default:
                    \Log::info("No specific cache clearing needed for {$apiName}");
                    break;
            }
        } catch (\Exception $e) {
            \Log::error("Failed to clear cache for {$apiName}: " . $e->getMessage());
        }
    }
}
