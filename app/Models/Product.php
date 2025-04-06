<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'price', 'stock_quantity',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
