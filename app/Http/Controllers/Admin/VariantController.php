<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product; // Import Product model
use App\Models\Variant;
use App\Models\VariantValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VariantController extends Controller
{
    // Store a newly created variant and its values
  // Store a newly created variant and its values
public function store(Request $request)
{
    // Define validation rules
    $validator = Validator::make($request->all(), [
        'product_id' => 'required|exists:products,id',
        'variants' => 'required|array',
        'variants.*.type' => 'required|string|max:255',
        'variants.*.values' => 'required|array',
        'variants.*.values.*.value' => 'required|string|max:255',
        'variants.*.values.*.price' => 'nullable|numeric|min:0',
        'variants.*.values.*.stock' => 'nullable|integer|min:0', // Add stock validation
        'variants.*.values.*.image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    // Handle validation failures
    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Loop through each variant in the request
        foreach ($request->variants as $variantData) {
            // Check if the variant already exists
            $variant = Variant::where('product_id', $request->product_id)
                ->where('type', $variantData['type'])
                ->first();

            // If the variant does not exist, create it
            if (!$variant) {
                $variant = Variant::create([
                    'product_id' => $request->product_id,
                    'type' => $variantData['type']
                ]);
            }

            foreach ($variantData['values'] as $value) {
                // Check if this value already exists for the current variant
                $existingValue = VariantValue::where('variant_id', $variant->id)
                    ->where('value', $value['value'])
                    ->first();

                // If it doesn't exist, create a new variant value
                if (!$existingValue) {
                    // Check if an image is provided and store it
                    $imagePath = null;
                    if (isset($value['image']) && $value['image']) {
                        $imagePath = $value['image']->store('variant_images', 'public');
                    }

                    // Create the VariantValue
                    VariantValue::create([
                        'variant_id' => $variant->id,
                        'value' => $value['value'],
                        'price' => $value['price'] ?? null,
                        'stock' => $value['stock'] ?? 0, // Store stock level
                        'image' => $imagePath,
                    ]);
                }
            }
        }

        return response()->json(['success' => 'Variants created successfully.'], 201);
    } catch (\Exception $e) {
        Log::error('Error creating variants: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to create variants.'], 500);
    }
}

// Update a variant



public function index()
{
    try {
        // Fetch products that have variants, including their variant values
        $productsWithVariants = Product::with(['variants.variantValues'])
            ->has('variants') // Ensure to fetch only products that have variants
            ->get();

        // Log the number of products fetched
        Log::info('Fetched products with variants', ['count' => $productsWithVariants->count()]);

        return response()->json($productsWithVariants);
    } catch (\Exception $e) {
        // Log the error message
        Log::error('Error fetching products with variants: ' . $e->getMessage());

        return response()->json(['error' => 'Unable to fetch products with variants'], 500);
    }
}


public function show($productId)
{
    // Fetch the product along with its variants and their variant values
    $product = Product::with(['variants.variantValues'])->find($productId);

    // Check if the product exists
    if (!$product) {
        return response()->json(['message' => 'Product not found'], 404);
    }

    // Log the product data for debugging purposes
    Log::info('productVariants', ['product' => $product]);

    // Return the product along with its variants and variant values as a JSON response
    return response()->json($product);
}


    public function deleteAllVariants($productId)
    {
        try {
            // Find the product by ID
            $product = Product::findOrFail($productId);
            
            // Delete all variants and their associated variant values
            foreach ($product->variants as $variant) {
                $variant->variantValues()->delete(); // Delete variant values
                $variant->delete(); // Delete variant
            }
            
            // Log the deletion
            Log::info('Deleted all variants for product', ['product_id' => $productId]);
            
            return response()->json(['message' => 'All variants deleted successfully.'], 200);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error deleting variants: ' . $e->getMessage());
            
            return response()->json(['error' => 'Unable to delete variants'], 500);
        }
    }


  public function update(Request $request, $id)
{
    // Validate the incoming request data
    $request->validate([
        'variants' => 'required|array',
        'variants.*.type' => 'required|string', // Ensure each variant has a type
        'variants.*.values' => 'required|array',
        'variants.*.values.*.value' => 'required|string',
        'variants.*.values.*.price' => 'required|numeric',
        'variants.*.values.*.stock' => 'required|integer',
        'variants.*.values.*.color' => 'nullable|string',
        'variants.*.values.*.image' => 'nullable|image|max:2048', // Optional image upload
    ]);

    // Find the product by ID
    $product = Product::findOrFail($id);

    // Loop through the variants provided in the request
    foreach ($request->variants as $variantData) {
        // Find the variant by its type
        $variant = $product->variants()->where('type', $variantData['type'])->first();

        if ($variant) {
            // Loop through the values and update or create each value
            foreach ($variantData['values'] as $valueData) {
                // Find the existing value by its value
                $value = $variant->variantValues()->where('value', $valueData['value'])->first();

                if ($value) {
                    // Update the existing value attributes
                    $value->price = $valueData['price'];
                    $value->stock = $valueData['stock'];

                    // Handle image upload if exists
                    if (isset($valueData['image'])) {
                        $imagePath = $valueData['image']->store('variant_images', 'public');
                        $value->image = $imagePath;
                    }

                    $value->save(); // Save the updated value
                } else {
                    // If the value doesn't exist, create a new one
                    $newValue = $variant->variantValues()->create([
                        'value' => $valueData['value'],
                        'price' => $valueData['price'],
                        'stock' => $valueData['stock'],
                    ]);

                    // Handle image upload if exists
                    if (isset($valueData['image'])) {
                        $imagePath = $valueData['image']->store('variant_images', 'public');
                        $newValue->image = $imagePath;
                        $newValue->save(); // Save the new value after setting the image
                    }
                }
            }
        } 
    }

    return response()->json(['success' => 'Variants updated successfully!'], 200);
}

public function destroyValue($variantId, $valueId)
{
    // Find the variant by ID
    $variant = Variant::find($variantId);
    if (!$variant) {
        return response()->json(['error' => 'Variant not found'], 404);
    }

    // Find the variant value by ID and check its association with the variant
    $variantValue = VariantValue::where('id', $valueId)->where('variant_id', $variant->id)->first();
    if (!$variantValue) {
        return response()->json(['error' => 'Variant value not found or does not belong to this variant'], 404);
    }

    // Delete the variant value
    $variantValue->delete();

    return response()->json(['success' => 'Variant value deleted successfully']);
}

public function destroyvariant($productId, $variantId)
{
    Log::info('Attempting to delete variant with ID: ' . $variantId . ' for product ID: ' . $productId);

    // Find the product by ID
    $product = Product::find($productId);
    if (!$product) {
        Log::warning('Product not found for ID: ' . $productId);
        return response()->json(['message' => 'Product not found.'], 404);
    }

    // Find the variant by ID with eager loading of variant values
    $variant = Variant::with('variantValues')->where('id', $variantId)->where('product_id', $productId)->first();

    if (!$variant) {
        Log::warning('Variant not found for ID: ' . $variantId . ' and Product ID: ' . $productId);
        return response()->json(['message' => 'Variant not found.'], 404);
    }

    // Log the variant and its associated values before deletion
    Log::info('Variant found: ', $variant->toArray());
    Log::info('Associated variant values: ', $variant->variantValues->toArray());

    // Delete all associated variant values
    $deletedValuesCount = $variant->variantValues()->delete();
    Log::info('Deleted variant values count: ' . $deletedValuesCount);

    // Delete the variant itself
    $variant->delete();
    Log::info('Variant deleted successfully with ID: ' . $variantId);

    return response()->json(['message' => 'Variant and its values deleted successfully!'], 200);
}


}
