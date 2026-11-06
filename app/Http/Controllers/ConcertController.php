<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Api;
use App\Services\TicketmasterService;
use App\Services\LocationService;

class ConcertController extends Controller
{
    private $ticketmasterBaseUrl;
    private $eventbriteBaseUrl;

    public function __construct()
    {
        // Initialize $baseUrl from .env at runtime
        $this->ticketmasterBaseUrl = env('TICKETMASTER_BASE_URL');
        $this->eventbriteBaseUrl = env('EVENTBRITE_BASE_URL');
    }

    /**
     * Get nearby Country concerts based on user location (GPS or IP)
     */
    public function getNearbyPopConcerts(Request $request)
    {
        try {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $radius = min((int) $request->input('radius', 50), 50); // Enforce 50-mile max radius
            if ($radius <= 0) $radius = 50;
            $limit = (int) $request->input('limit', 50);

            // If no coordinates provided, try to get from IP
            if (!$latitude || !$longitude) {
                $locationService = new LocationService();
                $ipLocation = $locationService->getLocationFromIP();

                if ($ipLocation) {
                    $latitude = $ipLocation['latitude'];
                    $longitude = $ipLocation['longitude'];

                    \Log::info('Concert location detected from IP', [
                        'ip' => $locationService->getClientIP(),
                        'location' => $ipLocation
                    ]);
                } 
            }

            // Create cache key based on coordinates and radius
            $cacheKey = 'country_concerts_v15_' . round((float)$latitude, 2) . '_' . round((float)$longitude, 2) . '_' . $radius;

            // Check cache first (15 minutes for location-based data)
            $cachedConcerts = Cache::get($cacheKey);
            if ($cachedConcerts) {
                return response()->json($cachedConcerts);
            }

            // Use TicketmasterService to fetch concerts strictly within 50 miles
            $ticketmasterService = new TicketmasterService();
            $result = $ticketmasterService->searchCountryConcerts((float)$latitude, (float)$longitude, $radius, $limit);

            // Add location information to the response
            $result['location'] = [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius' => $radius,
                'detected_from' => $request->input('latitude') ? 'gps' : 'ip_geolocation'
            ];

            // Cache successful results
            Cache::put($cacheKey, $result, 900); // 15 minutes

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('Country Concerts API Error: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'concerts' => $this->getFallbackConcerts(),
                'location' => [
                    'latitude' => $latitude ?? '',
                    'longitude' => $longitude ?? '',
                    'radius' => 100,
                    'detected_from' => 'fallback'
                ]
            ]);
        }
    }

    /**
     * Get concerts based on city or user location
     */
    public function getConcerts(Request $request)
    {
        try {
            $city = $request->input('city');
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');

            \Log::info('Concert API called', [
                'city' => $city,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'all_params' => $request->all()
            ]);

            // Create cache key based on location
            $cacheKey = $city ?
                'concerts_city_' . strtolower(str_replace(' ', '_', $city)) :
                'concerts_location_' . $latitude . '_' . $longitude;

            // Check cache first (30 minutes)
            $cachedConcerts = Cache::get($cacheKey);
            if ($cachedConcerts) {
                \Log::info('Returning cached concerts', ['cache_key' => $cacheKey]);
                return response()->json($cachedConcerts);
            }

            // Try Ticketmaster first
            $concerts = $this->fetchFromTicketmaster($city, $latitude, $longitude);

            // If no results, try Eventbrite
            if (empty($concerts)) {
                \Log::info('No Ticketmaster results, trying Eventbrite');
                $concerts = $this->fetchFromEventbrite($city, $latitude, $longitude);
            }

            // Cache results for 30 minutes
            Cache::put($cacheKey, $concerts, 1800);

            return response()->json($concerts);

        } catch (\Exception $e) {
            \Log::error('Concert Controller Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch concerts',
                'message' => 'Please try again later'
            ], 500);
        }
    }

    /**
     * Fetch concerts from Ticketmaster API
     */
    private function fetchFromTicketmaster($city = null, $latitude = null, $longitude = null)
    {
        try {
            $apiKey = Api::getValue('ticketmaster_api') ?: env('TICKETMASTER_API_KEY');

            if (!$apiKey) {
                \Log::info('Ticketmaster API key not found, using fallback concerts');
                return $this->getFallbackConcerts($city);
            }

            $queryStartDate = now('America/New_York')->subDay()->format('Y-m-d');
            $endDate = now('America/New_York')->addMonths(12)->format('Y-m-d');

            $params = [
                'apikey' => $apiKey,
                'classificationName' => 'Music',
                'size' => 20,
                'sort' => 'date,asc',
                'startDateTime' => $queryStartDate . 'T00:00:00Z',
                'endDateTime' => $endDate . 'T23:59:59Z'
            ];

            // Add location parameters
            if ($city) {
                $params['city'] = $city;
                \Log::info('Searching concerts by city', ['city' => $city]);
            } elseif ($latitude && $longitude) {
                $params['latlong'] = $latitude . ',' . $longitude;
                $params['radius'] = '25'; // 25 miles radius for more precise results
                $params['unit'] = 'miles';
                $params['sort'] = 'distance,asc'; // Sort by distance when using location
                \Log::info('Searching concerts by location', ['lat' => $latitude, 'lng' => $longitude]);
            } else {
                // Default to major cities if no location provided
                $params['city'] = 'New York,Los Angeles,Chicago,Houston,Phoenix,Miami,Seattle,Denver';
                \Log::info('Searching concerts in major cities');
            }

            \Log::info('Ticketmaster API Request', $params);

            $fullUrl = $this->ticketmasterBaseUrl . '/events.json';
            \Log::info('Making Ticketmaster API request', [
                'url' => $fullUrl,
                'params' => $params
            ]);

            $response = Http::timeout(30)
                ->withOptions(['verify' => false]) // Disable SSL verification for development
                ->get($fullUrl, $params);

            \Log::info('Ticketmaster API response', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_preview' => substr($response->body(), 0, 500)
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $events = $data['_embedded']['events'] ?? [];

                \Log::info('Ticketmaster API success', [
                    'total_results' => $data['page']['totalElements'] ?? 0,
                    'events_count' => count($events),
                    'first_event' => $events[0]['name'] ?? 'No events'
                ]);

                return $this->formatTicketmasterData($events);
            } else {
                \Log::error('Ticketmaster API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $fullUrl,
                    'params' => $params
                ]);
                return [];
            }

        } catch (\Exception $e) {
            \Log::error('Ticketmaster fetch error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format Ticketmaster API data
     */
    private function formatTicketmasterData($events)
    {
        $formattedEvents = [];

        foreach ($events as $event) {
            $venue = $event['_embedded']['venues'][0] ?? null;
            $date = $event['dates']['start'] ?? null;

            $formattedEvents[] = [
                'id' => $event['id'] ?? null,
                'name' => $event['name'] ?? 'Unknown Event',
                'date' => $date['localDate'] ?? null,
                'time' => $date['localTime'] ?? null,
                'datetime' => isset($date['dateTime']) ? $date['dateTime'] : null,
                'venue' => [
                    'name' => $venue['name'] ?? 'Unknown Venue',
                    'address' => $venue['address']['line1'] ?? '',
                    'city' => $venue['city']['name'] ?? '',
                    'state' => $venue['state']['stateCode'] ?? '',
                    'country' => $venue['country']['countryCode'] ?? ''
                ],
                'price_range' => $this->extractPriceRange($event),
                'ticket_url' => $event['url'] ?? null,
                'image' => $this->extractImage($event),
                'genre' => $this->extractGenre($event),
                'source' => 'Ticketmaster'
            ];
        }

        return $formattedEvents;
    }

    /**
     * Fetch concerts from Eventbrite API (fallback)
     */
    private function fetchFromEventbrite($city = null, $latitude = null, $longitude = null)
    {
        try {
            $apiKey = Api::getValue('eventbrite_api') ?: env('EVENTBRITE_API_KEY');

            if (!$apiKey) {
                \Log::info('Eventbrite API key not found, using fallback data');
                return $this->getFallbackConcerts($city);
            }

            // Eventbrite API implementation would go here
            // For now, return fallback data
            return $this->getFallbackConcerts($city);

        } catch (\Exception $e) {
            \Log::error('Eventbrite fetch error: ' . $e->getMessage());
            return $this->getFallbackConcerts($city);
        }
    }

    /**
     * Extract price range from Ticketmaster event
     */
    private function extractPriceRange($event)
    {
        $priceRanges = $event['priceRanges'] ?? [];
        if (empty($priceRanges)) {
            return null;
        }

        $range = $priceRanges[0];
        $min = $range['min'] ?? null;
        $max = $range['max'] ?? null;
        $currency = $range['currency'] ?? 'USD';

        if ($min && $max && $min != $max) {
            return "$currency $min - $max";
        } elseif ($min) {
            return "$currency $min";
        }

        return null;
    }

    /**
     * Extract image from Ticketmaster event
     */
    private function extractImage($event)
    {
        $images = $event['images'] ?? [];

        // Find the best quality image
        foreach ($images as $image) {
            if ($image['width'] >= 640) {
                return $image['url'];
            }
        }

        // Fallback to first image
        return $images[0]['url'] ?? null;
    }

    /**
     * Extract genre from Ticketmaster event
     */
    private function extractGenre($event)
    {
        $classifications = $event['classifications'] ?? [];

        if (!empty($classifications)) {
            $genre = $classifications[0]['genre']['name'] ?? null;
            $subGenre = $classifications[0]['subGenre']['name'] ?? null;

            return $subGenre ?: $genre;
        }

        return 'Music';
    }

    /**
     * Get Country concerts for homepage prioritizing GPS coordinates, then city, then US-wide
     */
    public function getHomepageConcerts(Request $request)
    {
        try {
            $limit = 50;
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $radius = min((int) $request->input('radius', 50), 50);
            if ($radius <= 0) $radius = 50;

            // 1. If GPS coordinates provided, search strictly within 50 miles
            if ($latitude && $longitude) {
                $cacheKey = 'homepage_country_gps_v15_' . round((float)$latitude, 2) . '_' . round((float)$longitude, 2) . '_' . $radius;
                $cachedConcerts = Cache::get($cacheKey);
                if ($cachedConcerts !== null) {
                    return response()->json([
                        'success' => true,
                        'concerts' => $cachedConcerts,
                        'location' => 'Your Location',
                        'detected_from' => 'gps',
                        'radius' => $radius
                    ]);
                }

                $ticketmasterService = new TicketmasterService();
                $result = $ticketmasterService->searchCountryConcerts((float)$latitude, (float)$longitude, $radius, $limit);
                $concerts = ($result['success'] && !empty($result['concerts'])) ? $result['concerts'] : [];

                Cache::put($cacheKey, $concerts, 900);
                return response()->json([
                    'success' => true,
                    'concerts' => $concerts,
                    'location' => 'Your Location',
                    'detected_from' => 'gps',
                    'radius' => $radius,
                    'message' => empty($concerts) ? 'No concerts found within 50 miles of your location' : null
                ]);
            }

            // 2. If no GPS, detect user's local city & coordinates via IP
            $locationService = new LocationService();
            $ipLocation = $locationService->getLocationFromIP();
            if ($ipLocation && !empty($ipLocation['latitude']) && !empty($ipLocation['longitude'])) {
                $ipLat = (float) $ipLocation['latitude'];
                $ipLon = (float) $ipLocation['longitude'];
                $cityName = $ipLocation['city'] ?? 'Your Area';

                $cacheKey = 'homepage_country_ip_v15_' . round($ipLat, 2) . '_' . round($ipLon, 2) . '_' . $radius;
                $cachedConcerts = Cache::get($cacheKey);
                if ($cachedConcerts !== null) {
                    return response()->json([
                        'success' => true,
                        'concerts' => $cachedConcerts,
                        'location' => $cityName,
                        'detected_from' => 'ip',
                        'radius' => $radius
                    ]);
                }

                $ticketmasterService = new TicketmasterService();
                $result = $ticketmasterService->searchCountryConcerts($ipLat, $ipLon, $radius, $limit);
                $concerts = ($result['success'] && !empty($result['concerts'])) ? $result['concerts'] : [];

                Cache::put($cacheKey, $concerts, 900);
                return response()->json([
                    'success' => true,
                    'concerts' => $concerts,
                    'location' => $cityName,
                    'detected_from' => 'ip',
                    'radius' => $radius,
                    'message' => empty($concerts) ? 'No concerts found within 50 miles of your location' : null
                ]);
            }

            // 3. Fallback: Search within 50 miles of station market coordinates (Nashville default)
            $defaultLat = 36.1627;
            $defaultLon = -86.7816;
            $cacheKey = 'homepage_country_default_v15_' . $radius;
            $cachedConcerts = Cache::get($cacheKey);
            if ($cachedConcerts !== null) {
                return response()->json([
                    'success' => true,
                    'concerts' => $cachedConcerts,
                    'location' => 'Nashville Area',
                    'detected_from' => 'station_market',
                    'radius' => $radius
                ]);
            }

            $ticketmasterService = new TicketmasterService();
            $result = $ticketmasterService->searchCountryConcerts($defaultLat, $defaultLon, $radius, $limit);
            $concerts = ($result['success'] && !empty($result['concerts'])) ? $result['concerts'] : [];

            Cache::put($cacheKey, $concerts, 900);
            return response()->json([
                'success' => true,
                'concerts' => $concerts,
                'location' => 'Nashville Area',
                'detected_from' => 'station_market',
                'radius' => $radius,
                'message' => empty($concerts) ? 'No concerts found within 50 miles of your location' : null
            ]);

        } catch (\Exception $e) {
            \Log::error('Homepage concerts error: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'concerts' => $this->getFallbackConcerts(),
                'location' => 'United States'
            ]);
        }
    }

    /**
     * Get Pop concerts by location name (city search)
     */
    public function getConcertsByLocation(Request $request)
    {
        try {
            $location = $request->input('location');

            if (!$location || trim($location) === '') {
                return response()->json([
                    'success' => false,
                    'error' => 'Location is required',
                    'concerts' => []
                ]);
            }

            // Create cache key for location search
            $cacheKey = 'location_concerts_v13_' . strtolower(trim($location));

            // Check cache first (15 minutes)
            $cachedConcerts = Cache::get($cacheKey);
            if ($cachedConcerts) {
                return response()->json([
                    'success' => true,
                    'concerts' => $cachedConcerts,
                    'location' => trim($location)
                ]);
            }

            $limit = 50; // Limit for location-based search

            // Use TicketmasterService to fetch concerts by location
            $ticketmasterService = new TicketmasterService();
            $result = $ticketmasterService->searchConcertsByLocation($location, $limit);

            if ($result['success'] && !empty($result['concerts'])) {
                // Cache results for 15 minutes
                Cache::put($cacheKey, $result['concerts'], 900);

                return response()->json([
                    'success' => true,
                    'concerts' => $result['concerts'],
                    'location' => $result['location'] ?? trim($location)
                ]);
            } else {
                $fallback = $this->getFallbackConcerts(trim($location));
                return response()->json([
                    'success' => true,
                    'concerts' => $fallback,
                    'location' => trim($location)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Location concerts error: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'concerts' => $this->getFallbackConcerts(),
                'location' => 'United States'
            ]);
        }
    }

    /**
     * Get fallback concerts when API key is missing or no results found
     */
    /**
     * Get fallback country concerts when API key is missing or no results found
     */
    private function getFallbackConcerts($city = null)
    {
        \Log::info('Returning fallback country music concerts with Ticketmaster booking links');
        
        $userCity = $city ?: (\Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::user()->city : 'Nashville');
        $defaultImg = asset('hero.jpg');

        return [
            [
                'id' => 'country-tour-1',
                'name' => 'Morgan Wallen: One Night At A Time Stadium Tour',
                'artist' => 'Morgan Wallen',
                'date' => date('F j, Y', strtotime('+20 days')),
                'time' => '7:00 PM',
                'datetime' => date('Y-m-d\TH:i:s\Z', strtotime('+20 days')),
                'venue' => [
                    'name' => 'Nissan Stadium',
                    'address' => '1 Titan Way',
                    'city' => $userCity,
                    'state' => 'TN',
                    'country' => 'US'
                ],
                'price_range' => 'USD 89.00 - 350.00',
                'ticket_url' => 'https://www.ticketmaster.com/morgan-wallen-tickets/artist/2347700',
                'image' => $defaultImg,
                'genre' => 'Country',
                'source' => 'Ticketmaster'
            ],
            [
                'id' => 'country-tour-2',
                'name' => 'Luke Combs: Growin Up and Gettin Old Tour',
                'artist' => 'Luke Combs',
                'date' => date('F j, Y', strtotime('+35 days')),
                'time' => '6:30 PM',
                'datetime' => date('Y-m-d\TH:i:s\Z', strtotime('+35 days')),
                'venue' => [
                    'name' => 'AT&T Center',
                    'address' => '1 AT&T Center Pkwy',
                    'city' => $userCity,
                    'state' => 'TX',
                    'country' => 'US'
                ],
                'price_range' => 'USD 75.00 - 290.00',
                'ticket_url' => 'https://www.ticketmaster.com/luke-combs-tickets/artist/2180020',
                'image' => $defaultImg,
                'genre' => 'Country',
                'source' => 'Ticketmaster'
            ],
            [
                'id' => 'country-tour-3',
                'name' => 'Zach Bryan: The Quittin Time Tour',
                'artist' => 'Zach Bryan',
                'date' => date('F j, Y', strtotime('+48 days')),
                'time' => '7:30 PM',
                'datetime' => date('Y-m-d\TH:i:s\Z', strtotime('+48 days')),
                'venue' => [
                    'name' => 'Red Rocks Amphitheatre',
                    'address' => '18300 W Alameda Pkwy',
                    'city' => $userCity,
                    'state' => 'CO',
                    'country' => 'US'
                ],
                'price_range' => 'USD 95.00 - 410.00',
                'ticket_url' => 'https://www.ticketmaster.com/zach-bryan-tickets/artist/2717329',
                'image' => $defaultImg,
                'genre' => 'Country',
                'source' => 'Ticketmaster'
            ],
            [
                'id' => 'country-tour-4',
                'name' => 'Lainey Wilson: Countrys Cool Again Tour',
                'artist' => 'Lainey Wilson',
                'date' => date('F j, Y', strtotime('+60 days')),
                'time' => '7:00 PM',
                'datetime' => date('Y-m-d\TH:i:s\Z', strtotime('+60 days')),
                'venue' => [
                    'name' => 'Bridgestone Arena',
                    'address' => '501 Broadway',
                    'city' => $userCity,
                    'state' => 'TN',
                    'country' => 'US'
                ],
                'price_range' => 'USD 65.00 - 220.00',
                'ticket_url' => 'https://www.ticketmaster.com/lainey-wilson-tickets/artist/2471850',
                'image' => $defaultImg,
                'genre' => 'Country',
                'source' => 'Ticketmaster'
            ]
        ];
    }

    /**
     * Get concerts for trending/popular artists
     * Returns concerts for well-known Pop artists like Taylor Swift, Ed Sheeran, etc.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTrendingArtistsConcerts(Request $request)
    {
        try {
            $limit = $request->input('limit', 3); // Default 3 concerts per artist

            // Create cache key
            $cacheKey = 'trending_artists_concerts_v15_' . $limit;

            // Check cache first (15 minutes)
            $cachedConcerts = Cache::get($cacheKey);
            if ($cachedConcerts) {
                return response()->json([
                    'success' => true,
                    'concerts' => $cachedConcerts,
                    'source' => 'cache'
                ]);
            }

            // Use TicketmasterService to fetch trending artists concerts
            $ticketmasterService = new TicketmasterService();
            $result = $ticketmasterService->getTrendingArtistsConcerts($limit);

            if ($result['success']) {
                // Cache results for 15 minutes
                Cache::put($cacheKey, $result['concerts'], 900);

                return response()->json([
                    'success' => true,
                    'concerts' => $result['concerts'],
                    'total_artists' => $result['total_artists'],
                    'total_concerts' => $result['total_concerts'],
                    'source' => 'api'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to fetch trending artists concerts',
                    'concerts' => []
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in getTrendingArtistsConcerts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred while fetching trending artists concerts',
                'concerts' => []
            ]);
        }
    }
}