<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UploadedNews;
use App\Services\NewsService;

class NewsController extends Controller
{
    protected $newsService;

    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }

    /**
     * Get news items for general station API
     */
    public function getNews()
    {
        try {
            $news = $this->newsService->getStationNews();
            return response()->json($news);
        } catch (\Throwable $e) {
            \Log::error('NewsController getNews error: ' . $e->getMessage());
            return response()->json($this->newsService->getFallbackNews());
        }
    }

    /**
     * Get news items for pop culture / station genre feed
     */
    public function getPopCultureNews()
    {
        try {
            $news = $this->newsService->getStationNews();

            return response()->json([
                'success' => true,
                'data' => $news,
                'count' => count($news)
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error fetching station news: ' . $e->getMessage());
            $fallback = $this->newsService->getFallbackNews();
            return response()->json([
                'success' => true,
                'data' => $fallback,
                'count' => count($fallback)
            ]);
        }
    }

    /**
     * Get entertainment headlines
     */
    public function getEntertainmentHeadlines()
    {
        try {
            $headlines = $this->newsService->getStationNews();

            return response()->json([
                'success' => true,
                'data' => $headlines,
                'count' => count($headlines)
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error fetching entertainment headlines: ' . $e->getMessage());
            $fallback = $this->newsService->getFallbackNews();
            return response()->json([
                'success' => true,
                'data' => $fallback,
                'count' => count($fallback)
            ]);
        }
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
                    'publishedAt' => $news->published_at ? $news->published_at->toISOString() : now()->toISOString(),
                    'is_uploaded' => true
                ];
            }

            return response()->json($formattedNews);

        } catch (\Throwable $e) {
            \Log::error('Error fetching uploaded news: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    public function showUploadedNews()
    {
        $news = UploadedNews::active()->ordered()->get();
        return view('news.uploaded-news', compact('news'));
    }
}