<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\ShopifyService;

class CheckoutController extends Controller
{
    protected $shopifyService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    public function index(Request $request)
    {
        $meta = $request->input('meta', []);
        $amount = $request->input('amount', 0);
        $currency = $request->input('currency', 'USD');

        return Inertia::render('Checkout', [
            'items' => $meta['items'] ?? [],
            'amount' => $amount,
            'currency' => $currency,
            'returnUrl' => $request->input('returnUrl', '/cart'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'shipping_address.first_name' => 'required|string',
            'shipping_address.last_name' => 'required|string',
            'shipping_address.address1' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.province' => 'required|string',
            'shipping_address.zip' => 'required|string',
            'shipping_address.phone' => 'required|string',
            'shipping_address.country' => 'required|string',
        ]);

        return to_route('payment.index', [
            'checkout_email' => $validated['email'],
            'checkout_shipping_address' => $validated['shipping_address'],
            'amount' => $request->input('amount'),
            'currency' => $request->input('currency'),
            'items' => $request->input('items', []),
        ]);
    }

    public function applyDiscount(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'product_id' => 'required',
        ]);

        $result = $this->shopifyService->applyDiscount($request->code, $request->product_id);

        return response()->json($result);
    }
}
