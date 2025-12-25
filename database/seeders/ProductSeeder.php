<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Brand\Models\Brand;
use App\Modules\Brand\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get brands
        $rankmath = Brand::where('slug', 'rankmath')->first();
        $wpRocket = Brand::where('slug', 'wp-rocket')->first();
        $imagify  = Brand::where('slug', 'imagify')->first();
        $backwpup = Brand::where('slug', 'backwpup')->first();

        if ($rankmath === null || $wpRocket === null || $imagify === null || $backwpup === null) {
            $this->command->error('Brands not found. Run BrandSeeder first.');

            return;
        }

        $products = [
            // RankMath Products
            [
                'brand_id'  => $rankmath->id,
                'name'      => 'RankMath Pro',
                'slug'      => 'rankmath-pro',
                'max_seats' => 5,
                'is_active' => true,
            ],
            [
                'brand_id'  => $rankmath->id,
                'name'      => 'Content AI',
                'slug'      => 'content-ai',
                'max_seats' => 5,
                'is_active' => true,
            ],
            [
                'brand_id'  => $rankmath->id,
                'name'      => 'RankMath Business',
                'slug'      => 'rankmath-business',
                'max_seats' => 10,
                'is_active' => true,
            ],

            // WP Rocket Products
            [
                'brand_id'  => $wpRocket->id,
                'name'      => 'WP Rocket Single',
                'slug'      => 'wp-rocket-single',
                'max_seats' => 1,
                'is_active' => true,
            ],
            [
                'brand_id'  => $wpRocket->id,
                'name'      => 'WP Rocket Plus',
                'slug'      => 'wp-rocket-plus',
                'max_seats' => 3,
                'is_active' => true,
            ],
            [
                'brand_id'  => $wpRocket->id,
                'name'      => 'WP Rocket Infinite',
                'slug'      => 'wp-rocket-infinite',
                'max_seats' => -1, // Unlimited
                'is_active' => true,
            ],

            // Imagify Products
            [
                'brand_id'  => $imagify->id,
                'name'      => 'Imagify',
                'slug'      => 'imagify',
                'max_seats' => 1,
                'is_active' => true,
            ],
            [
                'brand_id'  => $imagify->id,
                'name'      => 'Imagify Pro',
                'slug'      => 'imagify-pro',
                'max_seats' => -1,
                'is_active' => true,
            ],

            // BackWPup Products
            [
                'brand_id'  => $backwpup->id,
                'name'      => 'BackWPup Pro',
                'slug'      => 'backwpup-pro',
                'max_seats' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }

        $this->command->info('✓ Created ' . \count($products) . ' products');
    }
}
