<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Brand\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(bool $silent = false): void
    {
        $brands = [
            [
                'name'      => 'RankMath',
                'slug'      => 'rankmath',
                'api_key'   => Brand::generateApiKey('rankmath'),
                'is_active' => true,
            ],
            [
                'name'      => 'WP Rocket',
                'slug'      => 'wp-rocket',
                'api_key'   => Brand::generateApiKey('wp-rocket'),
                'is_active' => true,
            ],
            [
                'name'      => 'Imagify',
                'slug'      => 'imagify',
                'api_key'   => Brand::generateApiKey('imagify'),
                'is_active' => true,
            ],
            [
                'name'      => 'BackWPup',
                'slug'      => 'backwpup',
                'api_key'   => Brand::generateApiKey('backwpup'),
                'is_active' => true,
            ],
        ];

        foreach ($brands as $brandData) {
            Brand::create($brandData);
        }

        if (!$silent) {
            $this->command->info('✓ Created ' . \count($brands) . ' brands');
        }
    }
}
