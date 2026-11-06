<?php

use Illuminate\Support\Facades\Route;
use App\Services\LocationService;

// Debug route for testing IP-based location detection
Route::get('/debug/location', function() {
    try {
        $locationService = new LocationService();
        $clientIP = $locationService->getClientIP();
        $ipLocation = $locationService->getLocationFromIP();
        
        return response()->json([
            'client_ip' => $clientIP,
            'is_private_ip' => $locationService->isPrivateIP($clientIP),
            'ip_location' => $ipLocation,
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Debug route for testing the complete concerts API without coordinates
Route::get('/debug/concerts-auto', function() {
    try {
        $controller = new App\Http\Controllers\ConcertController();
        $request = new Illuminate\Http\Request();
        
        return $controller->getNearbyPopConcerts($request);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Debug route for testing concerts with specific coordinates
Route::get('/debug/concerts-coords', function(Illuminate\Http\Request $request) {
    try {
        $latitude = $request->query('lat', 40.7128); // Default: New York
        $longitude = $request->query('lng', -74.0060); // Default: New York
        $radius = $request->query('radius', 50);
        
        $controller = new App\Http\Controllers\ConcertController();
        $testRequest = new Illuminate\Http\Request([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius,
            'classification' => 'Pop'
        ]);
        
        $response = $controller->getNearbyPopConcerts($testRequest);
        
        return response()->json([
            'test_coordinates' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius' => $radius,
                'location_name' => getTestLocationName($latitude, $longitude)
            ],
            'api_response' => $response->getData(true),
            'timestamp' => now()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Test route for displaying concert cards styling with real data
Route::get('/test-concert-cards', function() {
    try {
        // Use real Ticketmaster API to get concert data
        $ticketmasterService = new App\Services\TicketmasterService();
        
        // Test with New York coordinates
        $latitude = 40.7128;
        $longitude = -74.0060;
        
        // Get real concert data from Ticketmaster API
        $apiResponse = $ticketmasterService->searchPopConcerts($latitude, $longitude, 50, 6);
        
        // Extract concerts from API response
        $concerts = $apiResponse['concerts'] ?? [];
        
        // If API fails or returns empty, fallback to mock data with real images
        if (empty($concerts)) {
            $concerts = [
                [
                    'id' => 'test_ny_1',
                    'name' => 'Sabrina Carpenter Short n Sweet Tour',
                    'artist' => 'Sabrina Carpenter',
                    'date' => 'October 15, 2025',
                    'time' => '19:30:00',
                    'venue' => [
                        'name' => 'Madison Square Garden',
                        'city' => 'New York',
                        'state' => 'NY',
                        'country' => 'US'
                    ],
                    'url' => 'https://www.ticketmaster.com/sabrina-carpenter-tickets/artist/2001092',
                    'image' => 'https://s1.ticketm.net/dam/a/1e8/1e8b8a8b7d3b0c005e8b8a8b7d3b0c00_170741_TABLET_LANDSCAPE_LARGE_16_9.jpg',
                    'genre' => 'Pop',
                    'price_range' => '$125 - $375'
                ],
                [
                    'id' => 'test_ny_2',
                    'name' => 'Chappell Roan The Rise and Fall of a Midwest Princess',
                    'artist' => 'Chappell Roan',
                    'date' => 'November 8, 2025',
                    'time' => '20:00:00',
                    'venue' => [
                        'name' => 'Barclays Center',
                        'city' => 'Brooklyn',
                        'state' => 'NY',
                        'country' => 'US'
                    ],
                    'url' => 'https://www.ticketmaster.com/tate-mcrae-miss-possessive-tour-new-york-new-york-09-03-2025/event/3B00616CF4C81D2B',
                    'image' => 'https://s1.ticketm.net/dam/a/2e8/2e8b8a8b7d3b0c005e8b8a8b7d3b0c00_170742_TABLET_LANDSCAPE_LARGE_16_9.jpg',
                    'genre' => 'Pop',
                    'price_range' => '$95 - $285'
                ],
                [
                    'id' => 'test_ny_3',
                    'name' => 'Tate McRae Think Later World Tour',
                    'artist' => 'Tate McRae',
                    'date' => 'December 3, 2025',
                    'time' => '19:00:00',
                    'venue' => [
                        'name' => 'Radio City Music Hall',
                        'city' => 'New York',
                        'state' => 'NY',
                        'country' => 'US'
                    ],
                    'url' => 'https://www.ticketmaster.com/tate-mcrae-miss-possessive-tour-new-york-new-york-09-03-2025/event/3B00616CF4C81D2B',
                    'image' => 'https://s1.ticketm.net/dam/a/3e8/3e8b8a8b7d3b0c005e8b8a8b7d3b0c00_170743_TABLET_LANDSCAPE_LARGE_16_9.jpg',
                    'genre' => 'Pop',
                    'price_range' => '$85 - $245'
                ],
                [
                    'id' => 'test_la_1',
                    'name' => 'Olivia Rodrigo GUTS World Tour',
                    'artist' => 'Olivia Rodrigo',
                    'date' => 'September 28, 2025',
                    'time' => '20:00:00',
                    'venue' => [
                        'name' => 'Crypto.com Arena',
                        'city' => 'Los Angeles',
                        'state' => 'CA',
                        'country' => 'US'
                    ],
                    'url' => 'https://www.ticketmaster.com/olivia-rodrigo-tickets/artist/2836194',
                    'image' => 'https://s1.ticketm.net/dam/a/4e8/4e8b8a8b7d3b0c005e8b8a8b7d3b0c00_170744_TABLET_LANDSCAPE_LARGE_16_9.jpg',
                    'genre' => 'Pop',
                    'price_range' => '$115 - $345'
                ],
                [
                    'id' => 'test_la_2',
                    'name' => 'Charlie Puth Something New Tour',
                    'artist' => 'Charlie Puth',
                    'date' => 'October 22, 2025',
                    'time' => '21:00:00',
                    'venue' => [
                        'name' => 'Kia Forum',
                        'city' => 'Inglewood',
                        'state' => 'CA',
                        'country' => 'US'
                    ],
                    'url' => 'https://www.ticketmaster.com/charlie-puth-tickets/artist/1964694',
                    'image' => 'https://s1.ticketm.net/dam/a/5e8/5e8b8a8b7d3b0c005e8b8a8b7d3b0c00_170745_TABLET_LANDSCAPE_LARGE_16_9.jpg',
                    'genre' => 'Pop',
                    'price_range' => '$95 - $275'
                ],
                [
                    'id' => 'test_la_3',
                    'name' => 'Doja Cat Scarlet Tour',
                    'artist' => 'Doja Cat',
                    'date' => 'November 15, 2025',
                    'time' => '19:30:00',
                    'venue' => [
                        'name' => 'Hollywood Bowl',
                        'city' => 'Los Angeles',
                        'state' => 'CA',
                        'country' => 'US'
                    ],
                    'url' => 'https://www.ticketmaster.com/tate-mcrae-miss-possessive-tour-new-york-new-york-09-03-2025/event/3B00616CF4C81D2B',
                    'image' => 'https://s1.ticketm.net/dam/a/6e8/6e8b8a8b7d3b0c005e8b8a8b7d3b0c00_170746_TABLET_LANDSCAPE_LARGE_16_9.jpg',
                    'genre' => 'Pop',
                    'price_range' => '$75 - $225'
                ]
            ];
        }
        
        return view('test-concert-cards', [
            'concerts' => $concerts,
            'location' => 'New York City, NY'
        ]);
        
    } catch (\Exception $e) {
        // Fallback to mock data if anything goes wrong
        $mockConcerts = [
            [
                'id' => 'test_ny_1',
                'name' => 'Sabrina Carpenter Short n Sweet Tour',
                'artist' => 'Sabrina Carpenter',
                'date' => 'October 15, 2025',
                'time' => '19:30:00',
                'venue' => [
                    'name' => 'Madison Square Garden',
                    'city' => 'New York',
                    'state' => 'NY',
                    'country' => 'US'
                ],
                'url' => 'https://www.ticketmaster.com/sabrina-carpenter-tickets/artist/2001092',
                'image' => 'https://s1.ticketm.net/dam/a/1e8/1e8b8a8b7d3b0c005e8b8a8b7d3b0c00_170741_TABLET_LANDSCAPE_LARGE_16_9.jpg',
                'genre' => 'Pop',
                'price_range' => '$125 - $375'
            ],
            [
                'id' => 'test_ny_2',
                'name' => 'Chappell Roan The Rise and Fall of a Midwest Princess',
                'artist' => 'Chappell Roan',
                'date' => 'November 8, 2025',
                'time' => '20:00:00',
                'venue' => [
                    'name' => 'Barclays Center',
                    'city' => 'Brooklyn',
                    'state' => 'NY',
                    'country' => 'US'
                ],
                'url' => 'https://www.ticketmaster.com/chappell-roan-tickets/artist/2397430',
                'image' => 'https://s1.ticketm.net/dam/a/2e8/2e8b8a8b7d3b0c005e8b8a8b7d3b0c00_170742_TABLET_LANDSCAPE_LARGE_16_9.jpg',
                'genre' => 'Pop',
                'price_range' => '$95 - $285'
            ],
            [
                'id' => 'test_ny_3',
                'name' => 'Tate McRae Think Later World Tour',
                'artist' => 'Tate McRae',
                'date' => 'December 3, 2025',
                'time' => '19:00:00',
                'venue' => [
                    'name' => 'Radio City Music Hall',
                    'city' => 'New York',
                    'state' => 'NY',
                    'country' => 'US'
                ],
                'url' => 'https://www.ticketmaster.com/tate-mcrae-tickets/artist/2720246',
                'genre' => 'Pop',
                'price_range' => '$85 - $245'
            ]
        ];
        
        return view('test-concert-cards', [
            'concerts' => $mockConcerts,
            'location' => 'New York City, NY (Fallback)'
        ]);
    }
});

// Helper function to get location name for testing
if (!function_exists('getTestLocationName')) {
    function getTestLocationName($latitude, $longitude) {
        // Simple coordinate to location name mapping for testing
        $locations = [
            '40.7128,-74.0060' => 'New York City, NY',
            '34.0522,-118.2437' => 'Los Angeles, CA',
            '41.8781,-87.6298' => 'Chicago, IL',
            '29.7604,-95.3698' => 'Houston, TX',
            '33.4484,-112.0740' => 'Phoenix, AZ',
            '39.9526,-75.1652' => 'Philadelphia, PA',
            '37.7749,-122.4194' => 'San Francisco, CA',
            '32.7767,-96.7970' => 'Dallas, TX',
            '47.6062,-122.3321' => 'Seattle, WA',
            '25.7617,-80.1918' => 'Miami, FL'
        ];
        
        $key = round($latitude, 4) . ',' . round($longitude, 4);
        return $locations[$key] ?? 'Unknown Location';
    }
}
