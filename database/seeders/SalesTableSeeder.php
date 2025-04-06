<?php

namespace Database\Seeders;
use App\Models\Sale;
use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;

class SalesTableSeeder extends Seeder
{
    public function run()
    {
        $users = User::pluck('id')->toArray();
        $products = Product::pluck('id')->toArray();

        foreach (range(1, 20) as $i) {
            $productId = $products[array_rand($products)];
            $quantity = rand(1, 5);
            $product = Product::find($productId);
            Sale::create([
                'user_id' => $users[array_rand($users)],
                'product_id' => $productId,
                'quantity' => $quantity,
                'total_price' => $quantity * $product->price,
            ]);
        }
    }
}
