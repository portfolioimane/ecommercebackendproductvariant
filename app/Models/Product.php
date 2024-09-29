<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'category_id',
        'image',
    ];

    // Relationship with variants
    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

     public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

