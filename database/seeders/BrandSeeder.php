<?php

namespace Database\Seeders;

use App\Models\Brand;
use Database\Seeders\Concerns\GeneratesPlaceholderImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    use GeneratesPlaceholderImages;

    private const BRANDS = [
        ['name' => 'Nova Tech', 'color' => [30, 41, 59]],
        ['name' => 'Vortex', 'color' => [79, 70, 229]],
        ['name' => 'Solaris', 'color' => [217, 119, 6]],
        ['name' => 'Atlas', 'color' => [15, 118, 110]],
        ['name' => 'Zenith', 'color' => [190, 24, 93]],
        ['name' => 'Orion', 'color' => [55, 65, 81]],
        ['name' => 'Pulse', 'color' => [220, 38, 38]],
        ['name' => 'Everline', 'color' => [5, 150, 105]],
    ];

    public function run(): void
    {
        foreach (self::BRANDS as $brand) {
            Brand::firstOrCreate(
                ['slug' => Str::slug($brand['name'])],
                [
                    'name' => $brand['name'],
                    'image' => $this->placeholderImage('brands', $brand['name'], $brand['color']),
                    'is_active' => true,
                ]
            );
        }
    }
}
