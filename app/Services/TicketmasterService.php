<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TicketmasterService
{
    protected $apiKey;
    protected $baseUrl;
    protected $stationFormat;
    protected $formatGenre;

    public function __construct()
    {
        $this->apiKey = \App\Models\Api::getValue('ticketmaster_api') ?: env('TICKETMASTER_API_KEY');
        $this->baseUrl = env('TICKETMASTER_BASE_URL', 'https://app.ticketmaster.com/discovery/v2');
        $this->stationFormat = \App\Models\SiteSetting::get('station_format', env('STATION_FORMAT', 'country'));
        $formatConfig = \App\Models\SiteSetting::getFormatConfig($this->stationFormat);
        $this->formatGenre = $formatConfig['ticketmaster_genre'] ?? ucfirst($this->stationFormat);
    }

    public static $majorCountryArtists = [
        // Major Headliners & Arena/Stadium Touring Stars
        'Morgan Wallen', 'Luke Combs', 'Zach Bryan', 'Lainey Wilson', 'Jelly Roll',
        'Chris Stapleton', 'Post Malone', 'Carrie Underwood', 'Blake Shelton', 'George Strait',
        'Kane Brown', 'Kacey Musgraves', 'Miranda Lambert', 'Cody Johnson', 'Tyler Childers',
        'Bailey Zimmerman', 'Thomas Rhett', 'Eric Church', 'Kenny Chesney', 'Tim McGraw',
        'Keith Urban', 'Jon Pardi', 'Cole Swindell', 'Jordan Davis', 'Megan Moroney',
        'Sam Hunt', 'Riley Green', 'Parker McCollum', 'HARDY', 'Dierks Bentley',
        'Dustin Lynch', 'Brad Paisley', 'Russell Dickerson', 'Brett Young', 'Chris Young',
        'Kip Moore', 'Dylan Scott', 'Carly Pearce', 'Kelsea Ballerini', 'Old Dominion',
        'Midland', 'Brothers Osborne', 'Turnpike Troubadours', 'Brantley Gilbert',
        'Justin Moore', 'Scotty McCreery', 'Chase Rice', 'Dan + Shay', 'Jason Aldean',
        'Luke Bryan', 'Lee Brice', 'Walker Hayes', 'Chris Lane', 'Tyler Hubbard',
        'Brian Kelley', 'Parmalee', 'LOCASH', 'LANCO', 'Maddie & Tae',
        // Rising & Breakthrough Signed Artists (Big & Getting Big)
        'Shaboozey', 'Zach Top', 'Warren Zeiders', 'Nate Smith', 'Ella Langley',
        'Gavin Adcock', 'Corey Kent', 'Dylan Gossett', 'Tucker Wetmore', 'Sam Barber',
        'Colby Acuff', 'Ian Munsick', 'Ashley Cooke', 'Jackson Dean', 'Conner Smith',
        'Chayce Beckham', 'Drew Baldridge', 'Kameron Marlowe', 'ERNEST', 'Redferrin',
        'Alana Springsteen', 'Avery Anna', 'Priscilla Block', 'Hailey Whitters', 'Dasha',
        'Wyatt Flores', 'Bryan Martin', 'George Birge', 'Stephen Wilson Jr.', 'The Castellows',
        'Carter Faith', 'Hudson Westbrook', 'Chase Matthew', 'Anne Wilson', 'Mitchell Tenpenny',
        // Red Dirt, Texas Country & Southern Rock Touring Acts
        'The Red Clay Strays', 'Whiskey Myers', 'Koe Wetzel', 'Treaty Oak Revival', 'Shane Smith & The Saints',
        'Flatland Cavalry', 'Pecos & The Rooftops', 'Muscadine Bloodline', 'Read Southall Band',
        'The Steel Woods', 'Blackberry Smoke', 'Marcus King', 'The Cadillac Three',
        'Cody Jinks', 'Whitey Morgan', 'Charley Crockett', 'Josh Abbott Band',
        'Randy Rogers Band', 'Aaron Watson', 'Wade Bowen', 'Casey Donahew', 'Pat Green',
        // Country Legends & Hall of Fame Touring Artists
        'Alabama', 'Brooks & Dunn', 'Shania Twain', 'Dolly Parton', 'Reba McEntire',
        'Willie Nelson', 'Hank Williams Jr.', 'Dwight Yoakam', 'Vince Gill', 'Alan Jackson',
        'Tanya Tucker', 'Clint Black', 'Travis Tritt', 'Tracy Lawrence', 'Gary Allan',
        'Randy Travis', 'Martina McBride', 'Lorrie Morgan', 'Diamond Rio', 'Sawyer Brown',
        'Collin Raye', 'Clay Walker', 'Sammy Kershaw', 'Little Big Town', 'Asleep at the Wheel',
        'Josh Turner', 'Toby Keith', 'Lonestar', 'Terri Clark', 'Jo Dee Messina', 'Sara Evans',
        // Major National Festivals & Tours
        'CMA Fest', 'Stagecoach', 'Country Thunder', 'Rock the South', 'Faster Horses',
        'Watershed', 'Tortuga', 'Country Jam', 'WE Fest', 'Two Step Inn', 'GoldenSky',
        'Carolina Country Music Fest', 'Buckeye Country Superfest', 'Country Calling'
    ];

    /**
     * Search for Major concerts near a specific GPS location strictly within 50 miles
     * 
     * @param float $latitude
     * @param float $longitude
     * @param int $radius Radius in miles (enforced max: 50)
     * @param int $limit Number of results to return (default: 30)
     * @return array
     */
    public function searchPopConcerts($latitude, $longitude, $radius = 50, $limit = 30)
    {
        return $this->searchCountryConcerts($latitude, $longitude, $radius, $limit);
    }

    public function searchCountryConcerts($latitude, $longitude, $radius = 50, $limit = 30)
    {
        try {
            if (!$this->apiKey) {
                Log::info('Ticketmaster API key not configured in TicketmasterService');
                return [
                    'success' => false,
                    'error' => 'Ticketmaster API key not configured',
                    'concerts' => []
                ];
            }

            // Enforce maximum 50-mile radius requirement strictly
            $radius = min((int)$radius, 50);
            if ($radius <= 0) {
                $radius = 50;
            }

            // Start from current date so Ticketmaster returns today and upcoming concerts
            $currentDate = now()->format('Y-m-d');
            $endDate = now()->addMonths(12)->format('Y-m-d');

            // 1. Query Ticketmaster API with strict 50-mile radius starting from current date
            $params = [
                'apikey' => $this->apiKey,
                'classificationName' => $this->formatGenre,
                'countryCode' => 'US',
                'latlong' => "{$latitude},{$longitude}",
                'radius' => $radius,
                'unit' => 'miles',
                'size' => 100,
                'sort' => 'date,asc',
                'startDateTime' => $currentDate . 'T00:00:00Z',
                'endDateTime' => $endDate . 'T23:59:59Z'
            ];

            $response = Http::timeout(10)->withoutVerifying()->get($this->baseUrl . '/events.json', $params);

            $events = [];
            if ($response->successful() && isset($response->json()['_embedded']['events'])) {
                $events = $response->json()['_embedded']['events'];
                $totalPages = $response->json()['page']['totalPages'] ?? 1;

                // In high-density markets like Nashville, fetch page 1 as well to ensure major upcoming tour dates are not missed
                if ($totalPages > 1) {
                    $params['page'] = 1;
                    $page2Response = Http::timeout(10)->withoutVerifying()->get($this->baseUrl . '/events.json', $params);
                    if ($page2Response->successful() && isset($page2Response->json()['_embedded']['events'])) {
                        $events = array_merge($events, $page2Response->json()['_embedded']['events']);
                    }
                }
            }

            // Strictly filter and format concerts within 50 miles starting from current date
            $concerts = $this->formatConcerts($events, (float)$latitude, (float)$longitude, $radius);

            // Slice to limit
            $concerts = array_slice($concerts, 0, $limit);

            return [
                'success' => true,
                'concerts' => $concerts,
                'total' => count($concerts),
                'message' => count($concerts) === 0 ? 'No major ' . $this->formatGenre . ' concerts found within 50 miles of your location' : null
            ];

        } catch (\Exception $e) {
            Log::error('Ticketmaster ' . $this->formatGenre . ' search error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to fetch ' . strtolower($this->formatGenre) . ' concerts: ' . $e->getMessage(),
                'concerts' => []
            ];
        }
    }

    /**
     * Search for Major Country concerts across the United States
     * 
     * @param int $limit Number of results to return (default: 30)
     * @return array
     */
    public function searchUSPopConcerts($limit = 30)
    {
        return $this->searchUSCountryConcerts($limit);
    }

    public function searchUSCountryConcerts($limit = 30)
    {
        try {
            if (!$this->apiKey) {
                Log::info('Ticketmaster API key not configured in TicketmasterService');
                return [
                    'success' => false,
                    'error' => 'Ticketmaster API key not configured',
                    'concerts' => []
                ];
            }

            $currentDate = now()->format('Y-m-d');
            $endDate = now()->addMonths(12)->format('Y-m-d');

            $params = [
                'apikey' => $this->apiKey,
                'classificationName' => 'Country',
                'countryCode' => 'US',
                'size' => 100,
                'sort' => 'date,asc',
                'startDateTime' => $currentDate . 'T00:00:00Z',
                'endDateTime' => $endDate . 'T23:59:59Z'
            ];

            $response = Http::timeout(10)->withoutVerifying()->get($this->baseUrl . '/events.json', $params);

            if (!$response->successful()) {
                Log::error('Ticketmaster API error (US search): ' . $response->body());
                return [
                    'success' => false,
                    'error' => 'Failed to fetch concerts from Ticketmaster API',
                    'concerts' => []
                ];
            }

            $data = $response->json();
            $events = $data['_embedded']['events'] ?? [];
            $totalPages = $data['page']['totalPages'] ?? 1;

            if ($totalPages > 1) {
                $params['page'] = 1;
                $page2Response = Http::timeout(10)->withoutVerifying()->get($this->baseUrl . '/events.json', $params);
                if ($page2Response->successful() && isset($page2Response->json()['_embedded']['events'])) {
                    $events = array_merge($events, $page2Response->json()['_embedded']['events']);
                }
            }

            $concerts = $this->formatConcerts($events);
            $concerts = array_slice($concerts, 0, $limit);

            return [
                'success' => true,
                'concerts' => $concerts,
                'total' => count($concerts)
            ];

        } catch (\Exception $e) {
            Log::error('Ticketmaster US Country search error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to fetch concerts: ' . $e->getMessage(),
                'concerts' => []
            ];
        }
    }

    /**
     * Search for Pop concerts by location name (city)
     * Uses OpenStreetMap Nominatim API for geocoding
     * 
     * @param string $location City name
     * @param int $limit Number of results to return
     * @return array
     */
    public function searchConcertsByLocation($location, $limit = 20)
    {
        try {
            // Check if API key is available
            if (!$this->apiKey) {
                Log::info('Ticketmaster API key not configured in TicketmasterService');
                return [
                    'success' => false,
                    'error' => 'Ticketmaster API key not configured',
                    'concerts' => []
                ];
            }

            // First, get coordinates for the location using OpenStreetMap Nominatim
            $geocodeUrl = "https://nominatim.openstreetmap.org/search";
            $geocodeParams = [
                'q' => $location,
                'format' => 'json',
                'limit' => 1,
                'countrycodes' => 'us'
            ];

            $appName = config('app.name', 'Radio');
            $geocodeResponse = Http::withoutVerifying()
                ->withHeaders(['User-Agent' => $appName . ' Concert App/1.0'])
                ->get($geocodeUrl, $geocodeParams);

            if (!$geocodeResponse->successful() || empty($geocodeResponse->json())) {
                return [
                    'success' => false,
                    'error' => 'Location not found. Please try a different city name.',
                    'concerts' => []
                ];
            }

            $geocodeData = $geocodeResponse->json()[0];
            $latitude = (float) $geocodeData['lat'];
            $longitude = (float) $geocodeData['lon'];
            $cityName = $geocodeData['display_name'];

            $result = $this->searchCountryConcerts($latitude, $longitude, 100, $limit);
            if ($result['success']) {
                $result['location'] = $cityName;
            }
            return $result;

        } catch (\Exception $e) {
            Log::error('Ticketmaster service error (location search): ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to fetch concerts: ' . $e->getMessage(),
                'concerts' => []
            ];
        }
    }

    /**
     * Get concerts for trending/popular artists
     * Searches for concerts by well-known popular artists
     * 
     * @param int $limit Number of concerts to return per artist
     * @return array
     */
    public function getTrendingArtistsConcerts($limit = 3)
    {
        try {
            // List of trending/popular Country music artists to search for
            $trendingArtists = [
                'Morgan Wallen',
                'Luke Combs',
                'Zach Bryan',
                'Lainey Wilson',
                'Jelly Roll',
                'Chris Stapleton',
                'Post Malone',
                'Carrie Underwood',
                'Blake Shelton',
                'George Strait',
                'Kane Brown',
                'Kacey Musgraves',
                'Miranda Lambert'
            ];

            $allConcerts = [];
            $foundArtists = [];

            // Get current date and end date (12 months from now)
            $currentDate = now()->format('Y-m-d');
            $endDate = now()->addMonths(12)->format('Y-m-d');

            foreach ($trendingArtists as $artist) {
                // Search for concerts by this artist starting from current date
                $response = Http::withoutVerifying()->get($this->baseUrl . '/events.json', [
                    'apikey' => $this->apiKey,
                    'keyword' => $artist,
                    'classificationName' => 'Country',
                    'countryCode' => 'US',
                    'size' => $limit,
                    'sort' => 'date,asc',
                    'startDateTime' => $currentDate . 'T00:00:00Z',
                    'endDateTime' => $endDate . 'T23:59:59Z'
                ]);

                if ($response->successful() && isset($response->json()['_embedded']['events'])) {
                    $events = $response->json()['_embedded']['events'];
                    $formattedConcerts = $this->formatConcerts($events);

                    // Filter to ensure we only get concerts for this specific artist
                    $artistConcerts = array_filter($formattedConcerts, function ($concert) use ($artist) {
                        return stripos($concert['artist'], $artist) !== false ||
                            stripos($concert['name'], $artist) !== false;
                    });

                    if (!empty($artistConcerts)) {
                        $allConcerts = array_merge($allConcerts, array_values($artistConcerts));
                        $foundArtists[] = $artist;
                    }
                }

                // Stop if we have enough artists with concerts
                if (count($foundArtists) >= 5) {
                    break;
                }
            }

            // Sort all concerts by date
            usort($allConcerts, function ($a, $b) {
                return strtotime($a['date'] . ' ' . ($a['time'] ?? '00:00')) -
                    strtotime($b['date'] . ' ' . ($b['time'] ?? '00:00'));
            });

            // Group concerts by artist for better display
            $groupedConcerts = [];
            foreach ($allConcerts as $concert) {
                $artistName = $concert['artist'];
                if (!isset($groupedConcerts[$artistName])) {
                    $groupedConcerts[$artistName] = [];
                }
                $groupedConcerts[$artistName][] = $concert;
            }

            return [
                'success' => true,
                'concerts' => $groupedConcerts,
                'total_artists' => count($groupedConcerts),
                'total_concerts' => count($allConcerts)
            ];

        } catch (\Exception $e) {
            Log::error('Ticketmaster service error (trending artists): ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to fetch trending artists concerts: ' . $e->getMessage(),
                'concerts' => []
            ];
        }
    }

    /**
     * Recurring institutional radio/variety broadcasts that are NOT individual touring concerts
     */
    private static $excludedShows = [
        'grand ole opry',
        'opry 100',
        'opry live',
        'opry country',
        'opry at the ryman',
        'friday night opry',
        'saturday night opry',
        'tuesday night opry',
        'midnite jamboree',
        'general jackson showboat',
        'country music hall of fame'
    ];

    /**
     * Non-country acts that Ticketmaster incorrectly tags under broad Country/Americana/Folk
     */
    private static $excludedNonCountryActs = [
        'indigo girls',
        'linda perry',
        'st. paul and the broken bones',
        'graham nash',
        'foreigner',
        'chicago',
        'counting crows',
        'train',
        'hanson',
        'gipsy kings',
        'bruce cockburn',
        'livingston taylor',
        'milk carton kids',
        'neko case',
        'punch brothers'
    ];

    /**
     * Specific local Nashville and regional small bars, dives, and clubs (NOT national concert venues)
     */
    private static $excludedLocalVenues = [
        'the basement',
        'the basement east',
        'bluebird cafe',
        'the listening room',
        'listening room cafe',
        'the 5 spot',
        'station inn',
        'exit/in',
        '3rd and lindsley',
        '3rd & lindsley',
        'commodore grille',
        'dee\'s country cocktail',
        'springwater supper club',
        'honky tonk central',
        'tootsie',
        'tootsies',
        'legends corner',
        'robert\'s western world',
        'the stage on broadway',
        'whiskey row',
        'tin roof'
    ];

    /**
     * Keywords that indicate low-quality, local bar fluff, or non-headliner events
     */
    private static $fluffKeywords = [
        'tribute', 'cover band', 'karaoke', 'trivia', 'bingo', 'dj set', 'dance party',
        'brunch', 'open mic', 'comedy', 'burlesque', 'wrestling', 'bar night', 'line dance',
        'line dancing', 'pub crawl', 'dance lessons', 'after party', 'tailgate party', 'tailgate',
        'saloon night', 'battle of the bands', 'tasting', 'drag', 'ladies night', 'country night',
        'whiskey wednesday', 'in the round', 'writer\'s round', 'writers round', 'songwriter round',
        'songwriters in the round', 'acoustic showcase', 'jam session', 'premium seating',
        'club seating', 'vip package', 'loft & tristar', 'parking only', 'parking pass',
        'fast pass', 'upgrade only', 'suite seating', 'soiree', 'beer garden', 'brewery'
    ];

    /**
     * Calculate geographic distance in miles between two coordinates using the Haversine formula
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float
     */
    public static function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadiusMiles = 3958.8;
        $dLat = deg2rad((float)$lat2 - (float)$lat1);
        $dLon = deg2rad((float)$lon2 - (float)$lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad((float)$lat1)) * cos(deg2rad((float)$lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadiusMiles * $c, 1);
    }

    /**
     * Format concert data for frontend display with strict quality, country filtering,
     * stable deduplication, and geographic radius enforcement.
     * Focuses strictly on signed country artists and major national shows.
     * 
     * @param array $events
     * @param float|null $originLat
     * @param float|null $originLon
     * @param float $maxRadius Maximum radius in miles (default: 50)
     * @return array
     */
    private function formatConcerts($events, $originLat = null, $originLon = null, $maxRadius = 50)
    {
        $formattedConcerts = [];
        $seenEvents = [];

        foreach ($events as $event) {
            // Only process events with venue information
            if (!isset($event['_embedded']['venues'][0])) {
                continue;
            }

            $name = $event['name'] ?? '';
            $artist = $this->extractArtistName($event);
            $lowerText = strtolower($name . ' ' . $artist);

            // 1. Exclude Grand Ole Opry recurring radio broadcasts and institutional variety shows
            $isExcludedShow = false;
            foreach (self::$excludedShows as $exShow) {
                if (stripos($lowerText, $exShow) !== false) {
                    $isExcludedShow = true;
                    break;
                }
            }
            if ($isExcludedShow || stripos($artist, 'Grand Ole Opry') !== false) {
                continue;
            }

            // 2. Exclude known non-country acts that slip through Ticketmaster classifications
            $isExcludedNonCountry = false;
            foreach (self::$excludedNonCountryActs as $nonCountryAct) {
                if (stripos($lowerText, $nonCountryAct) !== false) {
                    $isExcludedNonCountry = true;
                    break;
                }
            }
            if ($isExcludedNonCountry) {
                continue;
            }

            // 3. Exclude obvious fluff, tributes, covers, bar nights, writers rounds, parking, and VIP addons
            $isFluff = false;
            foreach (self::$fluffKeywords as $keyword) {
                if (stripos($lowerText, $keyword) !== false) {
                    $isFluff = true;
                    break;
                }
            }
            if ($isFluff) {
                continue;
            }

            $venue = $event['_embedded']['venues'][0];
            $venueName = $venue['name'] ?? '';
            $lowerVenue = strtolower($venueName);

            // 4. Exclude specific local Nashville/regional small bars and clubs
            $isExcludedLocalVenue = false;
            foreach (self::$excludedLocalVenues as $exVenue) {
                if (stripos($lowerVenue, $exVenue) !== false) {
                    $isExcludedLocalVenue = true;
                    break;
                }
            }
            if ($isExcludedLocalVenue) {
                continue;
            }

            // 5. Exclude small local dive bars, taprooms, pubs, and basement lounges
            $isSmallBarVenue = (bool) preg_match('/\b(brewery|cafe|café|tap room|taproom|pub|tavern|inn|grill|speakeasy|dive|lounge|saloon|basement)\b/i', $venueName);
            if ($isSmallBarVenue) {
                continue;
            }

            // 6. Must be a recognized major national venue (arenas, amphitheaters, stadiums, theaters, fairs, pavilions, auditoriums)
            $isMajorVenue = (bool) preg_match('/(stadium|arena|amphitheater|amphitheatre|center|centre|pavilion|theatre|theater|coliseum|dome|auditorium|hall|ballroom|stage|bowl|field|park|ranch|fair|fairground|fairgrounds|festival|fest|expo|speedway|complex|racetrack|symphony|convention)/i', $venueName);

            $imageUrl = $this->getEventImage($event);

            // Exclude events that use Ticketmaster's default generic crowd/sign stock placeholder
            if ($imageUrl && stripos($imageUrl, 'c58dd0af-cda8-49b2-bdaf-068540c3a677_106541') !== false) {
                continue;
            }

            // 7. Validate signed artist or verified major national tour based on station format
            $genre = $this->extractGenre($event);
            $subGenre = $event['classifications'][0]['subGenre']['name'] ?? '';

            if (strtolower($this->stationFormat) === 'country') {
                $isMajorArtist = false;
                foreach (self::$majorCountryArtists as $majorArtist) {
                    if (stripos($artist, $majorArtist) !== false || stripos($name, $majorArtist) !== false) {
                        $isMajorArtist = true;
                        break;
                    }
                }

                $isStrictCountryGenre = (
                    strcasecmp($genre ?? '', 'Country') === 0 ||
                    strcasecmp($subGenre ?? '', 'Country') === 0
                );

                // If not a recognized signed country artist and not strictly Country genre, skip
                if (!$isMajorArtist && !$isStrictCountryGenre) {
                    continue;
                }

                // If not in our signed artists list, enforce that it MUST be at a major national venue
                if (!$isMajorArtist && !$isMajorVenue) {
                    continue;
                }
            } else {
                // For non-country stations (pop, rock, punk, jazz, hip-hop, etc.)
                $isFormatGenre = (
                    stripos($genre ?? '', $this->formatGenre) !== false ||
                    stripos($subGenre ?? '', $this->formatGenre) !== false ||
                    stripos($genre ?? '', $this->stationFormat) !== false ||
                    stripos($subGenre ?? '', $this->stationFormat) !== false
                );

                if (!$isFormatGenre && !$isMajorVenue) {
                    continue;
                }
            }

            // Calculate distance strictly using API distance or Haversine formula
            $distance = isset($event['distance']) ? (float)$event['distance'] : null;
            $venueLat = isset($venue['location']['latitude']) ? (float)$venue['location']['latitude'] : null;
            $venueLon = isset($venue['location']['longitude']) ? (float)$venue['location']['longitude'] : null;

            if ($distance === null && $originLat !== null && $originLon !== null && $venueLat !== null && $venueLon !== null) {
                $distance = self::haversineDistance($originLat, $originLon, $venueLat, $venueLon);
            }

            // Strict 50-mile radius filter: Exclude events that are beyond maxRadius
            if ($originLat !== null && $originLon !== null && $distance !== null && $distance > (float)$maxRadius) {
                continue;
            }

            // 8. Stable event deduplication (preserves legitimate concerts with different venues or dates)
            $eventDate = $event['dates']['start']['localDate'] ?? '';
            $dedupKey = !empty($event['id'])
                ? (string) $event['id']
                : strtolower(trim($artist) . '|' . trim($venueName) . '|' . trim($eventDate));

            if (isset($seenEvents[$dedupKey])) {
                continue; // Skip duplicate instance of exact same event
            }
            $seenEvents[$dedupKey] = true;

            // Filter out any concerts in the past (ensure lineup starts with the current date)
            $today = now('America/New_York')->format('Y-m-d');
            if ($eventDate && $eventDate < $today) {
                continue;
            }

            // Calculate a quality score to prioritize major headliner tours
            $qualityScore = 0;
            if ($isMajorArtist) {
                $qualityScore += 50;
            }
            if ($isMajorVenue) {
                $qualityScore += 25;
            }

            $concert = [
                'id' => $event['id'],
                'name' => $name,
                'artist' => $artist,
                'date' => $this->formatDate($eventDate),
                'raw_date' => $eventDate ?: '9999-12-31',
                'time' => $event['dates']['start']['localTime'] ?? null,
                'venue' => [
                    'name' => $venueName,
                    'city' => $venue['city']['name'] ?? null,
                    'state' => $venue['state']['stateCode'] ?? null,
                    'country' => $venue['country']['countryCode'] ?? null,
                    'latitude' => $venueLat,
                    'longitude' => $venueLon
                ],
                'distance' => $distance !== null ? round($distance, 1) : null,
                'url' => $event['url'],
                'image' => $imageUrl,
                'genre' => $genre ?: 'Country',
                'price_range' => $this->extractPriceRange($event),
                'score' => $qualityScore
            ];

            $formattedConcerts[] = $concert;
        }

        // Sort strictly chronologically by date ascending so concerts start from current date
        usort($formattedConcerts, function ($a, $b) {
            $dateCmp = strcmp($a['raw_date'], $b['raw_date']);
            if ($dateCmp !== 0) {
                return $dateCmp;
            }
            // For concerts on the same date, prioritize major tours/venues, then by time
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            $timeA = $a['time'] ?? '00:00:00';
            $timeB = $b['time'] ?? '00:00:00';
            return strcmp($timeA, $timeB);
        });

        // Clean up internal properties before returning
        return array_map(function ($item) {
            unset($item['score'], $item['raw_date']);
            return $item;
        }, $formattedConcerts);
    }

    /**
     * Extract artist name from event data
     * 
     * @param array $event
     * @return string
     */
    private function extractArtistName($event)
    {
        // Try to get artist from attractions
        if (isset($event['_embedded']['attractions'][0]['name'])) {
            return $event['_embedded']['attractions'][0]['name'];
        }

        // Fallback to event name
        return $event['name'];
    }

    /**
     * Format date for display
     * 
     * @param string|null $date
     * @return string|null
     */
    private function formatDate($date)
    {
        if (!$date)
            return null;

        try {
            $dateTime = new \DateTime($date);
            return $dateTime->format('F j, Y');
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Get best high-resolution 16:9 event image URL
     * 
     * @param array $event
     * @return string|null
     */
    private function getEventImage($event)
    {
        if (empty($event['images'])) {
            return null;
        }

        $images = $event['images'];

        // Prefer 16_9 ratio with highest width >= 640
        $best169 = null;
        $maxWidth = 0;

        foreach ($images as $img) {
            $ratio = $img['ratio'] ?? '';
            $width = $img['width'] ?? 0;
            if ($ratio === '16_9' && $width > $maxWidth) {
                $best169 = $img['url'];
                $maxWidth = $width;
            }
        }

        if ($best169) {
            return $best169;
        }

        // Fallback to largest width image
        $largestImg = null;
        $maxWidth = 0;
        foreach ($images as $img) {
            $width = $img['width'] ?? 0;
            if ($width > $maxWidth) {
                $largestImg = $img['url'];
                $maxWidth = $width;
            }
        }

        return $largestImg ?: ($images[0]['url'] ?? null);
    }

    /**
     * Extract genre information
     * 
     * @param array $event
     * @return string|null
     */
    private function extractGenre($event)
    {
        if (isset($event['classifications'][0]['genre']['name'])) {
            return $event['classifications'][0]['genre']['name'];
        }

        return null;
    }

    /**
     * Extract price range
     * 
     * @param array $event
     * @return string|null
     */
    private function extractPriceRange($event)
    {
        if (isset($event['priceRanges'][0])) {
            $priceRange = $event['priceRanges'][0];
            $currency = $priceRange['currency'] ?? 'USD';
            $min = $priceRange['min'] ?? null;
            $max = $priceRange['max'] ?? null;

            if ($min && $max) {
                return $currency . ' ' . $min . ' - ' . $max;
            } elseif ($min) {
                return 'From ' . $currency . ' ' . $min;
            }
        }

        return null;
    }
}