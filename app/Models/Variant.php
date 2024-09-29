<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'product_id']; // Make sure to add product_id

    // Relationship with variant values
    public function variantValues()
    {
        return $this->hasMany(VariantValue::class);
    }

  public function product()
{
    return $this->belongsTo(Product::class);
}

}
