<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantValue extends Model
{
    use HasFactory;

    protected $fillable = ['variant_id', 'value', 'price','stock', 'image'];

    // Relationship with variant
    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}
