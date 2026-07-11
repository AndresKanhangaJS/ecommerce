<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\Concerns\GeneratesPlaceholderImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    use GeneratesPlaceholderImages;

    private const PRODUCTS = [
        'Eletrónica' => [
            'Smartphone Galaxy X10',
            'Auscultadores Bluetooth Pro',
            'Smartwatch Fit 2',
            'Powerbank 20000mAh',
        ],
        'Vestuário' => [
            'T-shirt Básica Algodão',
            'Calças de Ganga Slim',
            'Casaco Corta-Vento',
            'Ténis Running Air',
        ],
        'Casa & Jardim' => [
            'Conjunto de Panelas Antiaderente',
            'Aspirador Sem Fios',
            'Luminária de Mesa LED',
            'Kit de Jardinagem',
        ],
        'Desporto & Lazer' => [
            'Bola de Futebol Oficial',
            'Tapete de Yoga Antiderrapante',
            'Bicicleta Urbana Aro 26',
            'Halteres Ajustáveis 20kg',
        ],
        'Livros' => [
            'Romance Histórico - Edição Especial',
            'Livro de Receitas Saudáveis',
            'Guia de Programação PHP',
            'Best-seller de Ficção Científica',
        ],
        'Beleza & Saúde' => [
            'Creme Hidratante Facial',
            'Kit de Maquilhagem Profissional',
            'Escova de Dentes Elétrica',
            'Perfume Eau de Parfum 100ml',
        ],
    ];

    public function run(): void
    {
        $this->call([CategorySeeder::class, BrandSeeder::class]);

        $categories = Category::all()->keyBy('name');
        $brands = Brand::all();

        foreach (self::PRODUCTS as $categoryName => $productNames) {
            $category = $categories[$categoryName];

            foreach ($productNames as $name) {
                $brand = $brands->random();

                Product::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'category_id' => $category->id,
                        'brand_id' => $brand->id,
                        'name' => $name,
                        'description' => "Descrição de exemplo para {$name}, gerada pelo seeder de demonstração.",
                        'price' => fake()->randomFloat(2, 15, 850),
                        'images' => [$this->placeholderImage('products', $name, $this->colorFor($category->name))],
                        'is_active' => true,
                        'is_featured' => fake()->boolean(20),
                        'in_stock' => fake()->boolean(90),
                        'on_sale' => fake()->boolean(25),
                    ]
                );
            }
        }
    }

    private function colorFor(string $categoryName): array
    {
        $hash = crc32($categoryName);

        return [
            ($hash & 0xFF0000) >> 16,
            ($hash & 0x00FF00) >> 8,
            $hash & 0x0000FF,
        ];
    }
}
