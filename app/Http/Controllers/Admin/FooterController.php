<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Footer;
use Illuminate\Http\Request;

class FooterController extends Controller
{
    /**
     * Display the footer management page.
     * Redirects to unified Station Settings to eliminate duplicate configuration flows.
     */
    public function index()
    {
        return redirect()->route('admin.settings.index');
    }

    /**
     * Update the footer content.
     */
    public function update(Request $request)
    {
        $request->validate([
            // SEO Settings (Index Page)
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // Company Info Section
            'brand_name' => 'required|string|max:255',
            'description' => 'required|string',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'favicon' => 'nullable|file|mimes:png,ico|max:1024',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // Quick Links Section
            'home_link_text' => 'required|string|max:255',
            'home_link_url' => 'required|string|max:255',
            'news_link_text' => 'required|string|max:255',
            'news_link_url' => 'required|string|max:255',
            'concerts_link_text' => 'required|string|max:255',
            'concerts_link_url' => 'required|string|max:255',
            'events_link_text' => 'required|string|max:255',
            'events_link_url' => 'required|string|max:255',
            'contact_link_text' => 'required|string|max:255',
            'contact_link_url' => 'required|string|max:255',

            // Contact Info Section
            'address' => 'nullable|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'frequency' => 'nullable|string|max:255',

            // Social Media Links
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',

            // Copyright
            'copyright_text' => 'required|string|max:255',

            // Theme Settings
            'primary_blue' => 'required|string|max:7',
            'secondary_blue' => 'required|string|max:7',
            'accent_blue' => 'required|string|max:7',
            'accent_orange' => 'required|string|max:7',
            'accent_teal' => 'required|string|max:7',
            'warm_white' => 'required|string|max:7',
            'light_gray' => 'required|string|max:7',
            'soft_yellow' => 'required|string|max:7',
            'light_yellow' => 'required|string|max:7',
            'medium_gray' => 'required|string|max:7',
            'dark_gray' => 'required|string|max:7',
            'text_color' => 'required|string|max:7',
            'border_color' => 'required|string|max:7',
            'success_green' => 'required|string|max:7',
            'player_height' => 'required|string|max:10',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoName = 'Landscape_Logo.png';
            $logoPath = public_path($logoName);

            // Remove existing logo if it exists
            if (file_exists($logoPath)) {
                unlink($logoPath);
            }

            // Save the new logo
            $request->file('logo')->move(public_path(), $logoName);
        }
        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            $favicon = $request->file('favicon');

            // Decide extension dynamically (png or ico)
            $extension = $favicon->getClientOriginalExtension();

            $faviconName = 'favicon.' . $extension;
            $faviconPath = public_path($faviconName);

            // Remove existing favicon files (png & ico)
            if (file_exists(public_path('favicon.png'))) {
                unlink(public_path('favicon.png'));
            }
            if (file_exists(public_path('favicon.ico'))) {
                unlink(public_path('favicon.ico'));
            }

            // Save new favicon
            $favicon->move(public_path(), $faviconName);
        }
        // Handle SEO image upload
        $seoImagePath = null;

        if ($request->hasFile('seo_image')) {

            $seoImage = $request->file('seo_image');
            $seoImageName = 'seo-image.' . $seoImage->getClientOriginalExtension();

            // Create seo directory if not exists
            if (!is_dir(public_path('seo'))) {
                mkdir(public_path('seo'), 0755, true);
            }

            $seoImageFullPath = public_path('seo/' . $seoImageName);

            // Remove old SEO image if exists
            if (file_exists($seoImageFullPath)) {
                unlink($seoImageFullPath);
            }

            // Save new image
            $seoImage->move(public_path('seo'), $seoImageName);

            $seoImagePath = 'seo/' . $seoImageName;
        }

        // Prepare theme settings
        $themeSettings = [
            'primary_blue' => $request->primary_blue,
            'secondary_blue' => $request->secondary_blue,
            'accent_blue' => $request->accent_blue,
            'accent_orange' => $request->accent_orange,
            'accent_teal' => $request->accent_teal,
            'warm_white' => $request->warm_white,
            'light_gray' => $request->light_gray,
            'soft_yellow' => $request->soft_yellow,
            'light_yellow' => $request->light_yellow,
            'medium_gray' => $request->medium_gray,
            'dark_gray' => $request->dark_gray,
            'text_color' => $request->text_color,
            'border_color' => $request->border_color,
            'success_green' => $request->success_green,
            'player_height' => $request->player_height,
        ];

        $footer = Footer::getFooter();
        $seoSettings = [
            'title' => $request->seo_title,
            'description' => $request->seo_description,
            'image' => $seoImagePath ?? ($footer->seo_settings['image'] ?? null),
        ];

        // Update footer data including theme settings
        $footerData = $request->except(['seo_image', 'seo_title', 'seo_description', 'logo', 'favicon', '_token', '_method', 'primary_blue', 'secondary_blue', 'accent_blue', 'accent_orange', 'accent_teal', 'warm_white', 'light_gray', 'soft_yellow', 'light_yellow', 'medium_gray', 'dark_gray', 'text_color', 'border_color', 'success_green', 'player_height']);
        $footerData['theme_settings'] = $themeSettings;
        $footerData['seo_settings'] = $seoSettings;


        // Force social media URLs to '#' if empty or null
        $socialFields = [
            'facebook_url',
            'instagram_url',
            'twitter_url',
            'youtube_url',
        ];
        // Force address & frequency to '#' if empty or null
        $optionalFields = [
            'address',
            'frequency',
        ];

        foreach ($optionalFields as $field) {
            if (
                !isset($footerData[$field]) ||
                $footerData[$field] === null ||
                trim($footerData[$field]) === ''
            ) {
                $footerData[$field] = '#';
            }
        }

        foreach ($socialFields as $field) {
            if (
                !isset($footerData[$field]) ||
                $footerData[$field] === null ||
                trim($footerData[$field]) === ''
            ) {
                $footerData[$field] = '#';
            }
        }

        $footer->update($footerData);

        // Generate CSS file
        $this->generateThemeCss($themeSettings);

        return redirect()->route('admin.footer.index')
            ->with('success', 'Footer content and theme settings updated successfully!');
    }

    /**
     * Generate CSS file with theme variables
     */
    private function generateThemeCss($themeSettings)
    {
        try {
            $cssContent = ":root {\n";

            // Convert underscore keys to hyphens for CSS compatibility
            $cssVariables = [
                'primary_blue' => 'primary-blue',
                'secondary_blue' => 'secondary-blue',
                'accent_blue' => 'accent-blue',
                'accent_orange' => 'accent-orange',
                'accent_teal' => 'accent-teal',
                'warm_white' => 'warm-white',
                'light_gray' => 'light-gray',
                'soft_yellow' => 'soft-yellow',
                'light_yellow' => 'light-yellow',
                'medium_gray' => 'medium-gray',
                'dark_gray' => 'dark-gray',
                'text_color' => 'text-color',
                'border_color' => 'border-color',
                'success_green' => 'success-green',
                'player_height' => 'player-height'
            ];

            foreach ($themeSettings as $key => $value) {
                $cssVarName = $cssVariables[$key] ?? $key;
                $cssContent .= "    --{$cssVarName}: {$value};\n";
            }
            $cssContent .= "}\n";

            // Create css directory if it doesn't exist
            $cssDir = public_path('css');
            if (!is_dir($cssDir)) {
                mkdir($cssDir, 0755, true);
            }

            // Save to public/css/theme.css
            $cssPath = public_path('css/theme.css');
            $result = file_put_contents($cssPath, $cssContent);

            \Log::info('CSS file generated with hyphens');

        } catch (\Exception $e) {
            \Log::error('CSS generation failed: ' . $e->getMessage());
        }
    }
}