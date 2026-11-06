<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\SiteSetting;
use App\Models\Api;

class NewsService
{
    protected $apiKey;
    protected $baseUrl;
    protected $genre;
    protected $customQuery;
    protected $country;
    protected $itemsLimit;

    public function __construct()
    {
        $this->apiKey = SiteSetting::get('news_api_key') ?: (Api::getValue('news_api') ?: env('NEWS_API_KEY'));
        $this->baseUrl = SiteSetting::get('news_api_endpoint', env('NewsService_BASE_URL', 'https://newsapi.org/v2'));
        $this->baseUrl = rtrim($this->baseUrl, '/');
        
        $this->genre = SiteSetting::get('station_format', env('STATION_FORMAT', 'country'));
        $this->customQuery = trim(SiteSetting::get('news_search_query', ''));
        $this->country = strtolower(trim(SiteSetting::get('news_country', 'us')));
        $this->itemsLimit = (int) SiteSetting::get('news_items_limit', 12);
        if ($this->itemsLimit < 1) {
            $this->itemsLimit = 12;
        }
    }

    /**
     * Get news feed customized to the station's configuration
     *
     * @return array
     */
    public function getStationNews()
    {
        $cacheKey = 'station_music_news_' . $this->genre . '_v42_' . date('Y-m-d-H');

        return Cache::remember($cacheKey, 1800, function () {
            // 1. Fetch from dedicated Country Music feeds if genre is country
            $isCountry = strtolower($this->genre) === 'country';
            $dedicatedFeeds = $isCountry ? $this->fetchCountryRssFeeds() : [];
            if (!empty($dedicatedFeeds) && count($dedicatedFeeds) >= 6) {
                return $this->diversifyArticles($dedicatedFeeds, $this->itemsLimit);
            }

            // 2. If NewsAPI key is configured, query active genre news
            if ($this->apiKey) {
                try {
                    $queryParams = $this->buildQueryParams();
                    $endpoint = str_contains($this->baseUrl, 'top-headlines') ? $this->baseUrl : $this->baseUrl . '/everything';

                    $response = Http::timeout(10)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                        ])
                        ->withOptions(['verify' => false])
                        ->get($endpoint, $queryParams);

                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['articles']) && is_array($data['articles']) && !empty($data['articles'])) {
                            $formatted = $this->formatArticles($data['articles']);
                            if (!empty($formatted)) {
                                $merged = array_merge($dedicatedFeeds, $formatted);
                                return $this->diversifyArticles($merged, $this->itemsLimit);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::error('NewsService API Error: ' . $e->getMessage());
                }
            }

            // 3. Fallback to Google News RSS for the active genre
            $rssArticles = $this->fetchGoogleNewsRss($this->genre);
            if (!empty($rssArticles)) {
                $merged = array_merge($dedicatedFeeds, $rssArticles);
                return $this->diversifyArticles($merged, $this->itemsLimit);
            }

            // 4. Return whatever feeds we found or clean genre fallback
            return !empty($dedicatedFeeds) ? $this->diversifyArticles($dedicatedFeeds, $this->itemsLimit) : $this->getFallbackNews($this->genre);
        });
    }

    /**
     * Backward-compatible helper for pop culture news (Strictly Country Music)
     */
    public function getPopCultureNews()
    {
        return $this->getStationNews();
    }

    /**
     * Backward-compatible helper for entertainment headlines (Strictly Country Music)
     */
    public function getEntertainmentHeadlines()
    {
        return $this->getStationNews();
    }

    /**
     * Build NewsAPI query parameters tailored dynamically to the active Station Format
     */
    protected function buildQueryParams(): array
    {
        $formatCfg = SiteSetting::getFormatConfig($this->genre);
        $genreLabel = $formatCfg['label'];
        $keywords = $formatCfg['news_keywords'];

        if (!empty($this->customQuery)) {
            $q = $this->customQuery;
            $domains = null;
        } elseif (strtolower($this->genre) === 'country') {
            // Strictly Country Music Content - major country artists & country industry
            $q = '("country music" OR "country music star" OR "country singer" OR "country album" OR "Grand Ole Opry" OR "CMA Awards" OR "ACM Awards" OR "country tour" OR "Morgan Wallen" OR "Luke Combs" OR "Zach Bryan" OR "Lainey Wilson" OR "Chris Stapleton" OR "George Strait" OR "Cody Johnson")';
            $domains = 'tasteofcountry.com,countrynow.com,billboard.com,theboot.com';
        } else {
            // Multi-genre query for Pop, Rock, Punk, Jazz, Hip-Hop, etc.
            $q = "(\"{$genreLabel} music\" OR \"{$keywords}\" OR \"{$genreLabel} tour\" OR \"{$genreLabel} album\")";
            $domains = 'billboard.com,rollingstone.com,variety.com';
        }

        $params = [
            'q' => $q,
            'language' => 'en',
            'sortBy' => 'publishedAt',
            'pageSize' => $this->itemsLimit,
            'apiKey' => $this->apiKey
        ];

        if ($domains) {
            $params['domains'] = $domains;
        }

        return $params;
    }

    /**
     * Fetch dedicated Country Music feeds from Taste of Country, Country Now & Google News Country
     */
    protected function fetchCountryRssFeeds(): array
    {
        $feeds = [
            [
                'url' => 'https://tasteofcountry.com/feed/',
                'source' => 'Taste of Country',
                'dedicated' => true
            ],
            [
                'url' => 'https://countrynow.com/feed/',
                'source' => 'Country Now',
                'dedicated' => true
            ],
            [
                'url' => 'https://news.google.com/rss/search?q="country+music"+OR+"Grand+Ole+Opry"+OR+"Luke+Combs"+OR+"Morgan+Wallen"+OR+"Chris+Stapleton"&hl=en-US&gl=US&ceid=US:en',
                'source' => 'Country Music Scene',
                'dedicated' => false
            ]
        ];

        $allArticles = [];
        $defaultArt = asset(SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')));

        foreach ($feeds as $feed) {
            try {
                $response = Http::timeout(6)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'application/rss+xml, application/xml, text/xml, */*'
                    ])
                    ->withOptions(['verify' => false])
                    ->get($feed['url']);

                if (!$response->successful()) {
                    continue;
                }

                $xml = @simplexml_load_string($response->body());
                if (!$xml || !isset($xml->channel->item)) {
                    continue;
                }

                $isDedicated = !empty($feed['dedicated']);

                foreach ($xml->channel->item as $item) {
                    $title = trim((string) $item->title);
                    $link = trim((string) $item->link);
                    $pubDate = trim((string) $item->pubDate);
                    $rawDesc = (string) $item->description;
                    $description = strip_tags(html_entity_decode($rawDesc));

                    // Filter out fluff, non-music events, accidents, crime, politics, and require country relevance
                    if (!$this->isCountryMusicArticle($title, $description, $isDedicated)) {
                        continue;
                    }

                    // Extract image
                    $imageUrl = null;

                    // 1. Check enclosure tag
                    if (isset($item->enclosure) && !empty($item->enclosure['url'])) {
                        $imageUrl = (string) $item->enclosure['url'];
                    }

                    // 2. Check media:content or media:thumbnail
                    if (!$imageUrl) {
                        $namespaces = $item->getNamespaces(true);
                        if (isset($namespaces['media'])) {
                            $media = $item->children($namespaces['media']);
                            if (isset($media->content) && isset($media->content->attributes()['url'])) {
                                $imageUrl = (string) $media->content->attributes()['url'];
                            } elseif (isset($media->thumbnail) && isset($media->thumbnail->attributes()['url'])) {
                                $imageUrl = (string) $media->thumbnail->attributes()['url'];
                            }
                        }
                    }

                    // 3. Check for <img> in description
                    if (!$imageUrl && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $rawDesc, $match)) {
                        $imageUrl = $match[1];
                    }

                    // Force HTTPS on image
                    if (!empty($imageUrl) && str_starts_with($imageUrl, 'http://')) {
                        $imageUrl = 'https://' . substr($imageUrl, 7);
                    }

                    $cleanDesc = trim(preg_replace('/\s+/', ' ', $description));
                    if (strlen($cleanDesc) > 170) {
                        $cleanDesc = substr($cleanDesc, 0, 167) . '...';
                    }

                    $allArticles[] = [
                        'title'         => $title,
                        'description'   => $cleanDesc ?: ($title . ' - Country Music News'),
                        'content'       => $cleanDesc,
                        'author'        => $feed['source'],
                        'source'        => ['name' => $feed['source']],
                        'publishedAt'   => $pubDate ? date('Y-m-d\TH:i:s\Z', strtotime($pubDate)) : now()->toISOString(),
                        'url'           => $link,
                        'urlToImage'    => $imageUrl ?: $defaultArt,
                        'image'         => $imageUrl ?: $defaultArt,
                        'defaultImage'  => $defaultArt,
                        'formattedDate' => $this->formatDate($pubDate)
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Error fetching RSS feed ' . $feed['source'] . ': ' . $e->getMessage());
            }
        }

        return $this->diversifyArticles($allArticles, $this->itemsLimit * 2);
    }

    /**
     * Check if an article is genuine music news and not fluff, TV shows, real estate, recipes, crime, or politics.
     */
    protected function isCountryMusicArticle(string $title, string $description = '', bool $isDedicated = false): bool
    {
        $text = strtolower($title . ' ' . $description);

        // 1. Blacklisted non-music topics (TV/Movies/Series, Real Estate, Recipes, Sports, Crime, Politics, Fluff)
        $blacklistedKeywords = [
            // TV Shows, Movies, Series, Films & Actors (Non-music entertainment)
            'yellowstone', 'landman', '1883', '1923', 'tulsa king', 'lawmen', 'bass reeves', 'lioness',
            'mayor of kingstown', 'taylor sheridan', 'kevin costner', 'actor', 'actress', 'tv show',
            'tv series', 'television', 'season 1', 'season 2', 'season 3', 'season 4', 'season 5',
            'season 6', 'episode', 'episodes', 'finale', 'recap', 'movie', 'film', 'cinema',
            'starring', 'cast member', 'box office', 'theater', 'theatre', 'trailer', 'teaser',
            'netflix', 'hulu', 'hbo', 'paramount+', 'paramount network', 'peacock', 'disney+',
            'amazon prime', 'hallmark', 'reality tv', 'spin-off', 'dwts', 'dancing with the stars',
            'bachelor', 'bachelorette',

            // Real Estate & Houses
            'mansion', 'house for sale', 'bought a house', 'sold a house', 'real estate',
            'ranch for sale', 'home tour', 'property listing', 'interior design', 'zillow',

            // Food, Recipes, Kitchen & Lifestyle
            'recipe', 'cookbook', 'kitchen', 'restaurant', 'baking', 'menu', 'fast food', 'chef', 'cooking',
            'cocktail', 'bbq', 'dinner recipe',

            // Sports & Athletics
            'nfl', 'nba', 'mlb', 'nhl', 'nascar', 'football', 'basketball', 'baseball', 'hockey',
            'super bowl', 'quarterback', 'touchdown', 'draft', 'playoffs', 'wwe', 'wrestling', 'olympics',
            'travis kelce', 'chiefs', 'touchdowns', 'head coach',

            // Crime, Police, Disasters & Non-music Legal
            'arrest', 'arrested', 'shooting', 'shot dead', 'murder', 'police', 'crash', 'court',
            'trial', 'suspect', 'homicide', 'traffic', 'weather', 'storm', 'tornado', 'flood',
            'politics', 'election', 'democrat', 'republican', 'biden', 'trump', 'harris', 'senate', 'council',
            'governor', 'fatal', 'accident', 'investigation', 'robbery', 'deputy', 'sheriff', 'killed',
            'jail', 'prison', 'sentenced', 'stabbing', 'drug bust', 'dui', 'assault', 'firefighters',
            'body found', 'indicted', 'carjack', 'mugshot', 'lawsuit', 'verdict', 'guilty', 'warrant',
            'kardashian', 'paternity', 'lottery', 'powerball', 'mega millions'
        ];

        foreach ($blacklistedKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                // Allow exception ONLY if title/description explicitly indicates a genuine music release/performance
                $musicExceptionTerms = [
                    'soundtrack', 'theme song', 'performs live', 'sings live', 'music video',
                    'new album', 'new single', 'concert tour', 'grammy'
                ];
                $hasException = false;
                foreach ($musicExceptionTerms as $exc) {
                    if (str_contains($text, $exc)) {
                        $hasException = true;
                        break;
                    }
                }
                if (!$hasException) {
                    return false;
                }
            }
        }

        // 2. Positive Music Term Requirement
        $positiveMusicTerms = [
            'music', 'song', 'songs', 'album', 'albums', 'single', 'singles', 'track', 'tracks',
            'ep', 'cd', 'vinyl', 'record', 'records', 'chart', 'charts', 'billboard', 'tour', 'tours',
            'touring', 'concert', 'concerts', 'show', 'shows', 'stage', 'live performance',
            'performance', 'performer', 'setlist', 'singing', 'singer', 'singers', 'sing', 'sings',
            'sang', 'vocal', 'vocals', 'vocalist', 'guitar', 'guitarist', 'band', 'musician',
            'musicians', 'duet', 'collaboration', 'country music', 'pop music', 'rock music',
            'hip hop', 'opry', 'grand ole opry', 'cma', 'acm', 'grammy', 'grammys', 'cmt',
            'harmony', 'lyrics', 'lyric', 'songwriter', 'songwriting', 'hit', 'hits', 'stream',
            'streaming', 'spotify', 'apple music', 'radio', 'airplay', 'debut', 'music video',
            'headliner', 'headlining', 'opening act', 'arena', 'amphitheater', 'festival',
            'honky tonk', 'bluegrass', 'americana', 'stagecoach'
        ];

        $hasMusicTerm = false;
        foreach ($positiveMusicTerms as $term) {
            if (str_contains($text, $term)) {
                $hasMusicTerm = true;
                break;
            }
        }

        // 3. Known Music Artist Check
        $hasKnownArtist = false;
        $knownArtists = [
            'dolly parton', 'morgan wallen', 'luke combs', 'zach bryan', 'jelly roll', 'lainey wilson',
            'chris stapleton', 'george strait', 'cody johnson', 'carrie underwood', 'tim mcgraw',
            'kenny chesney', 'kacey musgraves', 'miranda lambert', 'jason aldean', 'blake shelton',
            'willie nelson', 'eric church', 'thomas rhett', 'parker mccollum', 'kane brown',
            'tyler childers', 'colter wall', 'sturgill simpson', 'post malone', 'megan moroney',
            'warren zeiders', 'turnpike troubadours', 'shaboozey', 'charley crockett', 'ella langley',
            'zach top', 'treaty oak revival', 'sam barber', 'chayce beckham', 'riley green',
            'dasha', 'nate smith', 'tucker wetmore', 'dierks bentley', 'keith urban', 'alan jackson',
            'reba mcentire', 'brooks & dunn', 'jon pardi', 'dustin lynch', 'midland', 'whiskey myers',
            'hardy', 'ernest', 'carly pearce', 'kelsea ballerini', 'maren morris', 'sam hunt',
            'jordan davis', 'walker hayes', 'russell dickerson', 'brett young', 'chris young',
            'lee brice', 'justin moore', 'josh turner', 'randy travis', 'garth brooks', 'vince gill',
            'toby keith', 'dwight yoakam', 'martina mcbride', 'shania twain', 'faith hill',
            'trisha yearwood', 'alabama', 'the judds', 'taylor swift', 'beyonce', 'beyoncé',
            'sabrina carpenter', 'chappell roan', 'billie eilish', 'olivia rodrigo', 'dua lipa',
            'harry styles', 'ed sheeran', 'bruno mars', 'lady gaga', 'ariana grande', 'justin bieber',
            'drake', 'kendrick lamar', 'eminem', 'sza', 'the weeknd', 'coldplay', 'green day'
        ];

        foreach ($knownArtists as $artist) {
            if (str_contains($text, $artist)) {
                $hasKnownArtist = true;
                break;
            }
        }

        // If from dedicated source: require either a positive music term OR a known artist mention
        if ($isDedicated) {
            return $hasMusicTerm || $hasKnownArtist;
        }

        // For general sources: require positive music term AND (known artist OR genre relevance)
        if (!$hasMusicTerm) {
            return false;
        }

        if ($hasKnownArtist) {
            return true;
        }

        $genreKeywords = [
            'country music', 'country singer', 'country artist', 'country song', 'country star',
            'country album', 'nashville', 'grand ole opry', 'opry', 'cma awards', 'acm awards',
            'cmt', 'honky tonk', 'bluegrass', 'americana', 'pop music', 'rock music', 'billboard hot 100',
            'top 40', 'chart topper', 'music festival', 'concert tour', 'world tour', 'album release'
        ];

        foreach ($genreKeywords as $gk) {
            if (str_contains($text, $gk)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format articles for consistent output & missing image resolution
     */
    protected function formatArticles(array $articles): array
    {
        $formatted = [];
        $defaultArt = asset(SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')));
        $formatCfg = SiteSetting::getFormatConfig($this->genre);

        foreach ($articles as $article) {
            if (empty($article['title']) || $article['title'] === '[Removed]') {
                continue;
            }

            $title = $article['title'];
            $desc = $article['description'] ?? '';

            if (strtolower($this->genre) === 'country' && !$this->isCountryMusicArticle($title, $desc)) {
                continue;
            }

            // Force HTTPS on image URL to prevent mixed-content browser blocks
            $rawImage = $article['urlToImage'] ?? '';
            if (!empty($rawImage) && str_starts_with($rawImage, 'http://')) {
                $rawImage = 'https://' . substr($rawImage, 7);
            }
            $imageUrl = !empty($rawImage) ? $rawImage : $defaultArt;

            $formatted[] = [
                'title'         => $title,
                'description'   => $desc ?: 'No description available',
                'content'       => $article['content'] ?? '',
                'author'        => $article['author'] ?? ($formatCfg['label'] . ' Music News'),
                'source'        => [
                    'name' => $article['source']['name'] ?? SiteSetting::get('default_news_source', 'Radio News')
                ],
                'publishedAt'   => $article['publishedAt'] ?? now()->toISOString(),
                'url'           => (!empty($article['url']) && $article['url'] !== '#') ? $article['url'] : 'https://www.billboard.com/',
                'urlToImage'    => $imageUrl,
                'image'         => $imageUrl,
                'defaultImage'  => $defaultArt,
                'formattedDate' => $this->formatDate($article['publishedAt'] ?? null)
            ];
        }

        return array_slice($formatted, 0, $this->itemsLimit);
    }

    /**
     * Genre-specific clean fallback news items
     */
    public function getFallbackNews(string $genre = 'pop'): array
    {
        $defaultArt = asset(SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')));
        $stationName = config('app.name');

        if ($genre === 'country') {
            return [
                [
                    'title' => 'Top Country Music Hits & Festival Tour Dates',
                    'description' => 'Catch up on breaking country music announcements, country chart-toppers, and upcoming US concert tour dates.',
                    'url' => 'https://tasteofcountry.com/',
                    'urlToImage' => $defaultArt,
                    'image' => $defaultArt,
                    'publishedAt' => now()->toISOString(),
                    'source' => ['name' => 'Taste of Country'],
                    'formattedDate' => now()->format('M j, Y')
                ],
                [
                    'title' => 'Country Music Stars & Industry News',
                    'description' => 'Get exclusive interviews, behind-the-scenes stories, and live performance highlights from top American country artists.',
                    'url' => 'https://countrynow.com/',
                    'urlToImage' => $defaultArt,
                    'image' => $defaultArt,
                    'publishedAt' => now()->subHours(2)->toISOString(),
                    'source' => ['name' => 'Country Now'],
                    'formattedDate' => now()->subHours(2)->format('M j, Y')
                ],
                [
                    'title' => 'Billboard Country Airplay & Album Charts',
                    'description' => 'Explore the latest Billboard Country charts, top streaming singles, and new country album reviews.',
                    'url' => 'https://www.billboard.com/c/music/music-news/country/',
                    'urlToImage' => $defaultArt,
                    'image' => $defaultArt,
                    'publishedAt' => now()->subHours(4)->toISOString(),
                    'source' => ['name' => 'Billboard Country'],
                    'formattedDate' => now()->subHours(4)->format('M j, Y')
                ],
                [
                    'title' => 'Grand Ole Opry & Country Concert News',
                    'description' => 'Stay informed with Grand Ole Opry line-ups, arena concert announcements, and country entertainment news.',
                    'url' => 'https://www.rollingstone.com/music/music-country/',
                    'urlToImage' => $defaultArt,
                    'image' => $defaultArt,
                    'publishedAt' => now()->subHours(6)->toISOString(),
                    'source' => ['name' => 'Rolling Stone Country'],
                    'formattedDate' => now()->subHours(6)->format('M j, Y')
                ]
            ];
        }

        // Default Pop Fallback
        return [
            [
                'title' => 'Top Pop Hits & Current US Chart Updates',
                'description' => 'Stay updated with breaking pop culture news, album releases, artist interviews, and chart toppers across the United States.',
                'url' => 'https://www.rollingstone.com/music/',
                'urlToImage' => $defaultArt,
                'image' => $defaultArt,
                'publishedAt' => now()->toISOString(),
                'source' => ['name' => 'Rolling Stone'],
                'formattedDate' => now()->format('M j, Y')
            ],
            [
                'title' => 'Billboard Hot 100 & Pop Culture Trends',
                'description' => 'Explore music charts, artist profiles, and in-depth coverage of current pop stars and concerts.',
                'url' => 'https://www.billboard.com/music/',
                'urlToImage' => $defaultArt,
                'image' => $defaultArt,
                'publishedAt' => now()->subHours(2)->toISOString(),
                'source' => ['name' => 'Billboard'],
                'formattedDate' => now()->subHours(2)->format('M j, Y')
            ],
            [
                'title' => 'New Pop Album Releases & Reviews',
                'description' => 'Check out the latest pop music releases, track reviews, and exclusive artist updates from top entertainment publications.',
                'url' => 'https://variety.com/c/music/',
                'urlToImage' => $defaultArt,
                'image' => $defaultArt,
                'publishedAt' => now()->subHours(4)->toISOString(),
                'source' => ['name' => 'Variety'],
                'formattedDate' => now()->subHours(4)->format('M j, Y')
            ],
            [
                'title' => 'Pop Music Concerts & Live Tour Announcements',
                'description' => 'Discover upcoming stadium tours, music festivals, and live performances from your favorite pop artists.',
                'url' => 'https://ew.com/music/',
                'urlToImage' => $defaultArt,
                'image' => $defaultArt,
                'publishedAt' => now()->subHours(6)->toISOString(),
                'source' => ['name' => 'Entertainment Weekly'],
                'formattedDate' => now()->subHours(6)->format('M j, Y')
            ]
        ];
    }

    /**
     * Format date for display
     */
    protected function formatDate(?string $dateString): string
    {
        if (!$dateString) {
            return now()->format('M j, Y');
        }

        try {
            $date = new \DateTime($dateString);
            return $date->format('M j, Y');
        } catch (\Throwable $e) {
            return now()->format('M j, Y');
        }
    }

    /**
     * Fetch real-time music news articles from Google News RSS by genre.
     */
    protected function fetchGoogleNewsRss(string $genre = 'country'): array
    {
        try {
            $formatCfg = SiteSetting::getFormatConfig($genre);
            $genreLabel = $formatCfg['label'];
            $keywords = $formatCfg['news_keywords'];

            $query = ($genre === 'country')
                ? 'country+music+news+OR+"country+music"+OR+"Grand+Ole+Opry"+OR+"Luke+Combs"+OR+"Morgan+Wallen"'
                : urlencode("\"{$genreLabel} music news\" OR \"{$keywords}\" OR \"{$genreLabel} tour\"");
            $rssUrl = "https://news.google.com/rss/search?q={$query}&hl=en-US&gl=US&ceid=US:en";

            $response = Http::timeout(8)->withOptions(['verify' => false])->get($rssUrl);
            if (!$response->successful()) {
                return [];
            }

            $xml = @simplexml_load_string($response->body());
            if (!$xml || !isset($xml->channel->item)) {
                return [];
            }

            $defaultArt = asset(SiteSetting::get('default_artwork_path', env('DEFAULT_ARTWORK_PATH', 'images/alexaicon.jpg')));
            $articles = [];
            $count = 0;

            foreach ($xml->channel->item as $item) {
                if ($count >= $this->itemsLimit) break;
                
                $title = trim((string) $item->title);
                $link = trim((string) $item->link);
                $pubDate = trim((string) $item->pubDate);
                $source = isset($item->source) ? (string) $item->source : ($genreLabel . ' Music News');

                // Clean title if source is appended at the end
                if (str_contains($title, ' - ')) {
                    $parts = explode(' - ', $title);
                    $source = array_pop($parts);
                    $title = implode(' - ', $parts);
                }

                $rawDesc = (string) ($item->description ?? '');
                $description = strip_tags(html_entity_decode($rawDesc));

                if (strtolower($genre) === 'country' && !$this->isCountryMusicArticle($title, $description)) {
                    continue;
                }

                $articles[] = [
                    'title' => $title,
                    'description' => $title . ' - Read the latest ' . strtolower($genreLabel) . ' music news updates and announcements.',
                    'url' => $link,
                    'urlToImage' => $defaultArt,
                    'image' => $defaultArt,
                    'defaultImage' => $defaultArt,
                    'publishedAt' => $pubDate ? date('Y-m-d\TH:i:s\Z', strtotime($pubDate)) : now()->toISOString(),
                    'source' => ['name' => $source],
                    'formattedDate' => $this->formatDate($pubDate),
                ];
                $count++;
            }

            return $articles;
        } catch (\Throwable $e) {
            Log::error('Error fetching Google News RSS: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generic content diversity and deduplication mechanism.
     * Prevents any single artist, person, or entity from dominating the news section,
     * while preserving legitimate distinct articles and ensuring balanced distribution.
     *
     * @param array $articles
     * @param int $limit
     * @param int $maxPerEntity
     * @return array
     */
    public function diversifyArticles(array $articles, int $limit = 12, int $maxPerEntity = 1): array
    {
        if (empty($articles)) {
            return [];
        }

        // 1. Deduplicate exact or near-duplicate titles
        $unique = [];
        $seenTitles = [];
        foreach ($articles as $art) {
            if (empty($art['title'])) continue;
            $norm = strtolower(preg_replace('/[^a-z0-9]/', '', $art['title']));
            if (!isset($seenTitles[$norm])) {
                $seenTitles[$norm] = true;
                $unique[] = $art;
            }
        }

        // 2. Extract all associated entities per article
        $articleEntities = [];
        foreach ($unique as $idx => $art) {
            $articleEntities[$idx] = $this->extractArticleEntities($art);
        }

        // 3. Selection with strict per-entity diversity constraint
        $selected = [];
        $entityCounts = [];
        $deferred = [];

        foreach ($unique as $idx => $art) {
            if (count($selected) >= $limit) {
                break;
            }

            $entities = $articleEntities[$idx];
            $canAdd = true;

            // Check if any recognized entity in this article already hit max count
            foreach ($entities as $entity) {
                if (($entityCounts[$entity] ?? 0) >= $maxPerEntity) {
                    $canAdd = false;
                    break;
                }
            }

            if ($canAdd) {
                $selected[] = $art;
                foreach ($entities as $entity) {
                    $entityCounts[$entity] = ($entityCounts[$entity] ?? 0) + 1;
                }
            } else {
                $deferred[] = $art;
            }
        }

        // 4. If we still have slots to reach $limit, fill with clean non-duplicate fallback articles
        if (count($selected) < $limit) {
            $fallbackList = $this->getFallbackNews('country');
            foreach ($fallbackList as $fb) {
                if (count($selected) >= $limit) break;
                $fbNorm = strtolower(preg_replace('/[^a-z0-9]/', '', $fb['title']));
                if (!isset($seenTitles[$fbNorm])) {
                    $seenTitles[$fbNorm] = true;
                    $selected[] = $fb;
                }
            }
        }

        // 5. If still below limit and we have deferred articles, only allow them if maxPerEntity is bumped
        if (count($selected) < $limit && !empty($deferred)) {
            foreach ($deferred as $defArt) {
                if (count($selected) >= $limit) break;
                $selected[] = $defArt;
            }
        }

        return array_slice($selected, 0, $limit);
    }

    /**
     * Dynamically extracts all primary subjects/entities (artists, performers, celebrities)
     * associated with an article by inspecting title, description, and metadata.
     *
     * @param array $article
     * @return array
     */
    public function extractArticleEntities(array $article): array
    {
        $entities = [];

        // 1. Check explicit metadata fields
        foreach (['artist', 'performer', 'entity'] as $field) {
            if (!empty($article[$field])) {
                $entities[] = strtolower(trim($article[$field]));
            }
        }

        $title = $article['title'] ?? '';
        $desc = $article['description'] ?? ($article['content'] ?? '');
        $combinedText = strtolower($title . ' ' . $desc);
        $titleLower = strtolower($title);

        // 2. Comprehensive artist and celebrity dictionary (all genres / country focus)
        $artistMap = [
            'dolly parton'         => 'dolly parton',
            'dolly'                => 'dolly parton',
            'morgan wallen'        => 'morgan wallen',
            'luke combs'           => 'luke combs',
            'zach bryan'           => 'zach bryan',
            'jelly roll'           => 'jelly roll',
            'lainey wilson'        => 'lainey wilson',
            'chris stapleton'      => 'chris stapleton',
            'george strait'        => 'george strait',
            'cody johnson'         => 'cody johnson',
            'carrie underwood'     => 'carrie underwood',
            'tim mcgraw'           => 'tim mcgraw',
            'kenny chesney'        => 'kenny chesney',
            'kacey musgraves'      => 'kacey musgraves',
            'miranda lambert'      => 'miranda lambert',
            'jason aldean'         => 'jason aldean',
            'blake shelton'        => 'blake shelton',
            'willie nelson'        => 'willie nelson',
            'eric church'          => 'eric church',
            'thomas rhett'         => 'thomas rhett',
            'parker mccollum'      => 'parker mccollum',
            'kane brown'           => 'kane brown',
            'tyler childers'       => 'tyler childers',
            'colter wall'          => 'colter wall',
            'sturgill simpson'     => 'sturgill simpson',
            'post malone'          => 'post malone',
            'megan moroney'        => 'megan moroney',
            'warren zeiders'       => 'warren zeiders',
            'turnpike troubadours' => 'turnpike troubadours',
            'shaboozey'            => 'shaboozey',
            'charley crockett'     => 'charley crockett',
            'ella langley'         => 'ella langley',
            'zach top'             => 'zach top',
            'treaty oak revival'   => 'treaty oak revival',
            'sam barber'           => 'sam barber',
            'chayce beckham'       => 'chayce beckham',
            'riley green'          => 'riley green',
            'dasha'                => 'dasha',
            'nate smith'           => 'nate smith',
            'tucker wetmore'       => 'tucker wetmore',
            'dierks bentley'       => 'dierks bentley',
            'keith urban'          => 'keith urban',
            'alan jackson'         => 'alan jackson',
            'reba mcentire'        => 'reba mcentire',
            'reba'                 => 'reba mcentire',
            'brooks & dunn'        => 'brooks & dunn',
            'jon pardi'            => 'jon pardi',
            'dustin lynch'         => 'dustin lynch',
            'midland'              => 'midland',
            'whiskey myers'        => 'whiskey myers',
            'hardy'                => 'hardy',
            'ernest'               => 'ernest',
            'carly pearce'         => 'carly pearce',
            'kelsea ballerini'     => 'kelsea ballerini',
            'maren morris'         => 'maren morris',
            'sam hunt'             => 'sam hunt',
            'jordan davis'         => 'jordan davis',
            'walker hayes'         => 'walker hayes',
            'russell dickerson'    => 'russell dickerson',
            'brett young'          => 'brett young',
            'chris young'          => 'chris young',
            'lee brice'            => 'lee brice',
            'justin moore'         => 'justin moore',
            'josh turner'          => 'josh turner',
            'randy travis'         => 'randy travis',
            'garth brooks'         => 'garth brooks',
            'vince gill'           => 'vince gill',
            'toby keith'           => 'toby keith',
            'dwight yoakam'        => 'dwight yoakam',
            'martina mcbride'      => 'martina mcbride',
            'shania twain'         => 'shania twain',
            'faith hill'           => 'faith hill',
            'trisha yearwood'      => 'trisha yearwood',
            'alabama'              => 'alabama',
            'the judds'            => 'the judds',
            'beyonce'              => 'beyonce',
            'beyoncé'              => 'beyonce',
            'taylor swift'         => 'taylor swift',
            'miley cyrus'          => 'miley cyrus'
        ];

        // Search both title and description for known artist matches
        foreach ($artistMap as $needle => $canonical) {
            if (preg_match('/\b' . preg_quote($needle, '/') . '\b/i', $combinedText)) {
                $entities[] = $canonical;
            }
        }

        // 3. Generic proper noun detection in headline (Two capitalized words, e.g. "Artist Name")
        if (preg_match_all('/\b([A-Z][a-z]{2,}(?:\s+[A-Z][a-z]{2,})+)\b/u', $title, $matches)) {
            $genericPhrases = [
                'country music', 'music scene', 'united states', 'press release', 'breaking news',
                'album review', 'grand ole', 'grand ole opry', 'music video', 'hall fame', 'cma awards',
                'acm awards', 'nashville tennessee', 'taste country', 'country now', 'billboard country'
            ];
            foreach ($matches[1] as $phrase) {
                $lowerPhrase = strtolower(trim($phrase));
                if (!in_array($lowerPhrase, $genericPhrases) && !in_array($lowerPhrase, $entities)) {
                    $entities[] = $lowerPhrase;
                }
            }
        }

        // 4. Fallback if no specific entity was identified: first significant words of headline
        if (empty($entities)) {
            $words = preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/', '', $title)));
            $stopwords = ['a', 'an', 'the', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'up', 'about', 'into', 'over', 'after', 'how', 'why', 'what', 'when', 'is', 'are', 'was', 'were', 'and', 'or', 'but'];
            $filtered = array_values(array_filter($words, fn($w) => strlen($w) > 3 && !in_array($w, $stopwords)));
            if (!empty($filtered)) {
                $entities[] = implode(' ', array_slice($filtered, 0, 2));
            } else {
                $entities[] = 'general_news';
            }
        }

        return array_values(array_unique($entities));
    }
}