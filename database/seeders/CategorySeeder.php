<?php

namespace Database\Seeders;

use App\Models\Category;
use Database\Seeders\Concerns\GeneratesPlaceholderImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    use GeneratesPlaceholderImages;

    private const CATEGORIES = [
        ['name' => 'Eletrónica', 'color' => [37, 99, 235]],
        ['name' => 'Vestuário', 'color' => [219, 39, 119]],
        ['name' => 'Casa & Jardim', 'color' => [22, 163, 74]],
        ['name' => 'Desporto & Lazer', 'color' => [234, 88, 12]],
        ['name' => 'Livros', 'color' => [124, 58, 237]],
        ['name' => 'Beleza & Saúde', 'color' => [220, 38, 38]],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'image' => $this->placeholderImage('categories', $category['name'], $category['color']),
                    'is_active' => true,
                ]
            );
        }
    }
}
