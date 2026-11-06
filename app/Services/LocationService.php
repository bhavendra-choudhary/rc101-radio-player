<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LocationService
{

    private $ipApiUrl;
    private $ipInfoUrl;
    private $freeGeoIpUrl;

    public function __construct()
    {
        $this->ipApiUrl = env('IP_API_URL', 'http://ip-api.com/json');
        $this->ipInfoUrl = env('IPINFO_URL', 'https://ipinfo.io');
        $this->freeGeoIpUrl = env('FREEGEOIP_URL', 'https://freegeoip.app/json');
    }
    /**
     * Get user location from IP address
     */
    public function getLocationFromIP($ip = null)
    {
        try {
            // If no IP provided, get client IP
            if (!$ip) {
                $ip = $this->getClientIP();
            }

            // Skip localhost and private IPs
            if ($this->isPrivateIP($ip)) {
                return null;
            }

            // Create cache key
            $cacheKey = 'ip_location_' . md5($ip);

            // Check cache first (24 hours for IP locations)
            $cachedLocation = Cache::get($cacheKey);
            if ($cachedLocation) {
                return $cachedLocation;
            }

            // Try multiple geolocation services
            $location = $this->tryGeolocationServices($ip);

            if ($location) {
                // Cache successful result for 24 hours
                Cache::put($cacheKey, $location, 86400);
                return $location;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('IP Geolocation Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Try multiple geolocation services
     */
    private function tryGeolocationServices($ip)
    {
        // Try ip-api.com (free, no API key required)
        $location = $this->getFromIpApi($ip);
        if ($location) {
            return $location;
        }

        // Try ipinfo.io (free tier available)
        $location = $this->getFromIpInfo($ip);
        if ($location) {
            return $location;
        }

        // Try freegeoip.app (free service)
        $location = $this->getFromFreeGeoIp($ip);
        if ($location) {
            return $location;
        }

        return null;
    }

    /**
     * Get location from ip-api.com
     */
    private function getFromIpApi($ip)
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->ipApiUrl}/{$ip}", [
                    'fields' => 'lat,lon,city,country,regionName,status'
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'success' && isset($data['lat']) && isset($data['lon'])) {
                    return [
                        'latitude' => $data['lat'],
                        'longitude' => $data['lon'],
                        'city' => $data['city'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'country' => $data['country'] ?? null,
                        'source' => 'ip-api'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('ip-api.com error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get location from ipinfo.io
     */
    private function getFromIpInfo($ip)
    {
        try {
            $response = Http::timeout(5)
                ->get("$this->ipInfoUrl}/{$ip}/json");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['loc'])) {
                    $coords = explode(',', $data['loc']);
                    if (count($coords) === 2) {
                        return [
                            'latitude' => $coords[0],
                            'longitude' => $coords[1],
                            'city' => $data['city'] ?? null,
                            'region' => $data['region'] ?? null,
                            'country' => $data['country'] ?? null,
                            'source' => 'ipinfo'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('ipinfo.io error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get location from freegeoip.app
     */
    private function getFromFreeGeoIp($ip)
    {
        try {
            $response = Http::timeout(5)
                ->get("$this->freeGeoIpUrl}/{$ip}");

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['latitude']) && isset($data['longitude'])) {
                    return [
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'city' => $data['city'] ?? null,
                        'region' => $data['region_name'] ?? null,
                        'country' => $data['country_code'] ?? null,
                        'source' => 'freegeoip'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('freegeoip.app error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get client IP address
     */
    private function getClientIP()
    {
        $ipHeaders = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipHeaders as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Handle multiple IPs in X-Forwarded-For
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    /**
     * Check if IP is private/local
     */
    private function isPrivateIP($ip)
    {
        $privateRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
            '169.254.0.0/16',
            '::1/128',
            'fc00::/7',
            'fe80::/10'
        ];

        foreach ($privateRanges as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in range
     */
    private function ipInRange($ip, $range)
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        list($rangeIp, $netmask) = explode('/', $range, 2);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->ipv6InRange($ip, $rangeIp, $netmask);
        } else {
            return $this->ipv4InRange($ip, $rangeIp, $netmask);
        }
    }

    /**
     * Check IPv4 range
     */
    private function ipv4InRange($ip, $rangeIp, $netmask)
    {
        $ipDecimal = ip2long($ip);
        $rangeDecimal = ip2long($rangeIp);
        $wildcardDecimal = pow(2, (32 - $netmask)) - 1;
        $netmaskDecimal = ~$wildcardDecimal;

        return ($ipDecimal & $netmaskDecimal) === ($rangeDecimal & $netmaskDecimal);
    }

    /**
     * Check IPv6 range (simplified)
     */
    private function ipv6InRange($ip, $rangeIp, $netmask)
    {
        // Simplified IPv6 check - in production, use a proper library
        return strpos($ip, str_replace('::', '', $rangeIp)) === 0;
    }
}