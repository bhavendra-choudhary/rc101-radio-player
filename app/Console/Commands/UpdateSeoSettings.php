<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Footer;

class UpdateSeoSettings extends Command
{
    protected $signature   = 'seo:update {--force : Overwrite existing SEO values even if already set}';
    protected $description = 'Update the Footer SEO settings (title, description, social image) in the database';

    public function handle()
    {
        $footer = Footer::first();

        if (! $footer) {
            $this->error('No Footer record found in the database. Run your seeder or visit the admin panel first.');
            return 1;
        }

        $appName    = config('app.name');
        $existingSeo = $footer->seo_settings ?? [];

        $newTitle = $appName . ' — #1 Hit Music Radio, Stream Live 24/7 Free';
        $newDesc  = 'Listen to ' . $appName . ' live online — your #1 hit music radio station. '
                  . 'Stream the hottest songs, latest pop hits, and non-stop music 24 hours a day, '
                  . '7 days a week. Totally free.';
        $newImage = 'images/social-banner.png';

        // Show current values
        $this->line('');
        $this->info('Current SEO settings:');
        $this->line('  Title:       ' . ($existingSeo['title']       ?? '(none)'));
        $this->line('  Description: ' . ($existingSeo['description'] ?? '(none)'));
        $this->line('  Image:       ' . ($existingSeo['image']       ?? '(none)'));
        $this->line('');

        // Check if already optimal
        $titleLen = strlen($existingSeo['title'] ?? '');
        $descLen  = strlen($existingSeo['description'] ?? '');
        $alreadyGood = $titleLen >= 40 && $descLen >= 100;

        if ($alreadyGood && ! $this->option('force')) {
            $this->warn('SEO settings already look good (title: ' . $titleLen . ' chars, desc: ' . $descLen . ' chars).');
            $this->warn('Use --force to overwrite anyway.');
            return 0;
        }

        // Apply updates — keep any existing admin-customized values unless --force
        $updatedSeo = array_merge($existingSeo, [
            'title'       => $this->option('force') ? $newTitle  : (strlen($existingSeo['title']       ?? '') >= 40 ? $existingSeo['title']       : $newTitle),
            'description' => $this->option('force') ? $newDesc   : (strlen($existingSeo['description'] ?? '') >= 100 ? $existingSeo['description'] : $newDesc),
            'image'       => $this->option('force') ? $newImage  : (! empty($existingSeo['image'])      ? $existingSeo['image']       : $newImage),
        ]);

        $footer->seo_settings = $updatedSeo;
        $footer->save();

        $this->info('✅ SEO settings updated:');
        $this->line('  Title       (' . strlen($updatedSeo['title']) . ' chars): ' . $updatedSeo['title']);
        $this->line('  Description (' . strlen($updatedSeo['description']) . ' chars): ' . $updatedSeo['description']);
        $this->line('  Image: ' . $updatedSeo['image']);
        $this->line('');
        $this->info('Done! Clear your view cache if needed: php artisan view:clear');

        return 0;
    }
}
