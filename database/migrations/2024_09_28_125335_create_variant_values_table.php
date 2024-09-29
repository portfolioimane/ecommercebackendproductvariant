<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVariantValuesTable extends Migration
{
    public function up()
    {
        Schema::create('variant_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained()->onDelete('cascade'); // Reference to variants table
            $table->string('value'); // e.g., 'red', 'blue', 'small', 'medium'
            $table->decimal('price', 10, 2)->nullable(); // Optional price for this variant value
            $table->integer('stock')->default(0); // Add stock column with a default value of 0
            $table->string('image')->nullable(); // URL or path to the image for this variant value
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('variant_values');
    }
}

