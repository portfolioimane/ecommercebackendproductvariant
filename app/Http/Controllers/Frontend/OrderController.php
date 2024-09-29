<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\CartItem;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{

     public function getUserOrders()
{
    $userId = Auth::id();

    // Fetch orders for the authenticated user
    $orders = Order::where('user_id', $userId)->with('items.product')->get();

    return response()->json([
        'orders' => $orders,
    ]);
}


    public function show($id)
    {
        // Retrieve the order by ID
        $order = Order::with('items.product')->find($id);

        // Check if the order exists
        if (!$order) {
            return response()->json([
                'message' => 'Order not found.'
            ], 404);
        }

        // Optionally, you might want to check if the user is authorized to view the order
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized access to this order.'
            ], 403);
        }

        return response()->json([
            'order' => $order,
        ], 200);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'total_price' => 'required|numeric',
            'payment_method' => 'required|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
            'items.*.image' => 'sometimes|string|max:255',
        ]);

        // Create the order
        $order = Order::create([
            'user_id' => Auth::id(), // Only if user is logged in
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
        ]);

        // Create order items
        foreach ($request->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'image' => $item['image'],
            ]);
        }


    // Clear the user's cart
    $cart = Cart::where('user_id', Auth::id())->first();
    if ($cart) {
        // Delete all cart items
        $cart->items()->delete(); // Assuming you have a relationship defined in the Cart model
        // Optionally delete the cart itself if you don't need it anymore
        $cart->delete();
    }

        if ($request->input('payment_status') === 'paid') {
            // Handle successful payment logic here
            return response()->json(['order' => $order], 201);
        } elseif ($request->input('payment_status') === 'pending') {
            // Handle cash on delivery logic here
            return response()->json(['order' => $order], 201);
        } else {
            return response()->json(['error' => 'Invalid payment status'], 400);
        }
    }

    public function payment(Request $request)
    {
        $request->validate([
            'total_price' => 'required|numeric|min:0',
        ]);

         Stripe::setApiKey(config('services.stripe.secret'));
        $paymentIntent = PaymentIntent::create([
            'amount' => $request->input('total_price') * 100, // Convert to cents
            'currency' => 'mad',
        ]);

        return response()->json(['paymentIntent' => $paymentIntent]);
    }
}
