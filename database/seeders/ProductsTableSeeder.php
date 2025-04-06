<?php

namespace Database\Seeders;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    public function run()
    {
        Product::insert([
            ['name' => 'T-Shirt', 'price' => 19.99, 'stock_quantity' => 100],
            ['name' => 'Jeans', 'price' => 49.99, 'stock_quantity' => 50],
            ['name' => 'Sneakers', 'price' => 79.99, 'stock_quantity' => 30],
        ]);
    }
}

