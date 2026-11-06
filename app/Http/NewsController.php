<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Api;
use App\Models\UploadedNews;
use DOMDocument;
use DOMXPath;

class NewsController extends Controller
{
    private $baseUrl;

    public function __construct()
    {
        // Initialize $baseUrl from .env at runtime
        $this->baseUrl = env('NEWS_BASE_URL', 'https://newsapi.org/v2/everything');
    }

    public function getNews()
    {
        try {
            $newsService = new \App\Services\NewsService();
            $news = $newsService->getStationNews();
            return response()->json($news);
        } catch (\Exception $e) {
            \Log::error('NewsController Error: ' . $e->getMessage());
            $newsService = new \App\Services\NewsService();
            return response()->json($newsService->getFallbackNews('country'));
        }
    }

    private function fetchNewsFromAPI()
    {
        try {
            \Log::info('Fetching news from API...');

            // Get API key from database or environment (Database priority)
            $apiKey = Api::getValue('news_api') ?: env('NEWS_API_KEY');

            if (!$apiKey) {
                \Log::error('NewsAPI key not found');
                return $this->getFallbackNews();
            }

            $params = [
                'apiKey' => $apiKey,
                'q' => 'pop music OR pop artists OR music celebrities OR new album OR music charts OR pop stars OR music industry OR pop culture',
                'language' => 'en',
                'sortBy' => 'publishedAt',
                'pageSize' => 12,
                'domains' => 'rollingstone.com,variety.com,ew.com,people.com,usmagazine.com,pitchfork.com,spin.com,nme.com'
            ];

            

            \Log::info('API Parameters:', $params);

            $response = Http::timeout(30)
                ->withOptions(['verify' => false]) // Disable SSL verification for development
                ->get($this->baseUrl, $params);

            \Log::info('API Response Status:', ['status' => $response->status()]);

            if ($response->successful()) {
                $data = $response->json();
                \Log::info('API Response Data:', ['total_results' => $data['totalResults'] ?? 0, 'articles_count' => count($data['articles'] ?? [])]);
                return $this->formatNewsData($data['articles'] ?? []);
            } else {
                \Log::error('API Response Failed:', ['status' => $response->status(), 'body' => $response->body()]);
                return $this->getFallbackNews();
            }

        } catch (\Exception $e) {
            \Log::error('News API Error: ' . $e->getMessage());
            return $this->getFallbackNews();
        }
    }

    private function formatNewsData($articles)
    {
        $formattedNews = [];

        foreach (array_slice($articles, 0, 12) as $article) {
            $formattedNews[] = [
                'title' => $article['title'] ?? 'No Title',
                'description' => $this->truncateText($article['description'] ?? 'No description available', 120),
                'url' => $article['url'] ?? '#',
                'urlToImage' => $article['urlToImage'] ?? null,
                'image' => $article['urlToImage'] ?? null, // Fallback for compatibility
                'publishedAt' => $article['publishedAt'] ?? now(),
                'source' => [
                    'name' => $article['source']['name'] ?? 'Unknown Source'
                ]
            ];
        }

        return $formattedNews;
    }

    private function truncateText($text, $length = 120)
    {
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }

    private function getFallbackNews()
    {
        \Log::info('Returning enhanced fallback news with real music news links');
        
        return [
            [
                'title' => 'Discover the Latest Music News',
                'description' => 'Stay updated with breaking news, album releases, artist interviews, and music industry insights from top entertainment sources.',
                'url' => 'https://www.rollingstone.com/music/',
                'urlToImage' => asset('hero.jpg'),
                'image' => asset('hero.jpg'),
                'publishedAt' => now()->toISOString(),
                'source' => [
                    'name' => 'Rolling Stone'
                ]
            ],
            [
                'title' => 'Pop Culture & Celebrity Updates',
                'description' => 'Get the latest entertainment news, celebrity gossip, and pop culture trends from Hollywood and the music world.',
                'url' => 'https://variety.com/c/music/',
                'urlToImage' => asset('hero.jpg'),
                'image' => asset('hero.jpg'),
                'publishedAt' => now()->subHours(2)->toISOString(),
                'source' => [
                    'name' => 'Variety'
                ]
            ],
            [
                'title' => 'Music Charts & Industry News',
                'description' => 'Explore music charts, billboard rankings, artist profiles, and in-depth coverage of the entertainment industry.',
                'url' => 'https://www.billboard.com/music/',
                'urlToImage' => asset('hero.jpg'),
                'image' => asset('hero.jpg'),
                'publishedAt' => now()->subHours(4)->toISOString(),
                'source' => [
                    'name' => 'Billboard'
                ]
            ],
            [
                'title' => 'New Album Releases & Reviews',
                'description' => 'Check out the latest album releases, music reviews, and exclusive artist content from leading music publications.',
                'url' => 'https://pitchfork.com/',
                'urlToImage' => asset('hero.jpg'),
                'image' => asset('hero.jpg'),
                'publishedAt' => now()->subHours(6)->toISOString(),
                'source' => [
                    'name' => 'Pitchfork'
                ]
            ],
            [
                'title' => 'Music Festivals & Live Events',
                'description' => 'Discover upcoming music festivals, concert tours, and live performances from your favorite artists around the world.',
                'url' => 'https://www.nme.com/news/music',
                'urlToImage' => asset('hero.jpg'),
                'image' => asset('hero.jpg'),
                'publishedAt' => now()->subHours(8)->toISOString(),
                'source' => [
                    'name' => 'NME'
                ]
            ],
            [
                'title' => 'Entertainment Headlines & Trends',
                'description' => 'Stay informed with today\'s top entertainment stories, trending topics, and must-read features from the music industry.',
                'url' => 'https://ew.com/music/',
                'urlToImage' => asset('hero.jpg'),
                'image' => asset('hero.jpg'),
                'publishedAt' => now()->subHours(10)->toISOString(),
                'source' => [
                    'name' => 'Entertainment Weekly'
                ]
            ]
        ];
    }

    /**
     * Get uploaded news articles from the database
     */
    public function getUploadedNews()
    {
        try {
            $uploadedNews = UploadedNews::active()->ordered()->get();

            $formattedNews = [];
            foreach ($uploadedNews as $news) {
                $formattedNews[] = [
                    'title' => $news->title,
                    'description' => $news->content,
                    'url' => $news->source_url ?: '#',
                    'urlToImage' => $news->image_path ? asset('storage/' . $news->image_path) : null,
                    'source' => [
                        'name' => $news->source_name
                    ],
                    'publishedAt' => $news->published_at->toISOString(),
                    'is_uploaded' => true // Flag to identify uploaded news
                ];
            }

            return response()->json($formattedNews);

        } catch (\Exception $e) {
            \Log::error('Error fetching uploaded news: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Get top 12 most relevant and recent pop culture news
     */
    public function getPopCultureNews()
    {
        try {
            $newsService = new \App\Services\NewsService();
            $popCultureNews = $newsService->getPopCultureNews();

            return response()->json([
                'success' => true,
                'data' => $popCultureNews,
                'count' => count($popCultureNews)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching pop culture news: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => [],
                'count' => 0,
                'error' => 'Failed to fetch pop culture news'
            ]);
        }
    }

    /**
     * Get entertainment headlines
     */
    public function getEntertainmentHeadlines()
    {
        try {
            $newsService = new \App\Services\NewsService();
            $headlines = $newsService->getEntertainmentHeadlines();

            return response()->json([
                'success' => true,
                'data' => $headlines,
                'count' => count($headlines)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching entertainment headlines: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'data' => [],
                'count' => 0,
                'error' => 'Failed to fetch entertainment headlines'
            ]);
        }
    }
    public function showUploadedNews()
    {
        // 1. Get active & ordered news from database
        $news = UploadedNews::active()
            ->ordered()
            ->get();

        // 2. Pass data to Blade view
        return view('news.uploaded-news', compact('news'));
    }

}