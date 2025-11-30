<?php

namespace App\Http\Controllers;

use App\Models\StripeCustomer;
use App\Services\ShopifyService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected $stripeService;
    protected $shopifyService;

    public function __construct(StripeService $stripeService, ShopifyService $shopifyService)
    {
        $this->stripeService = $stripeService;
        $this->shopifyService = $shopifyService;
    }

    public function index(Request $request)
    {
        $email = $request->input('checkout_email');
        $shipping = $request->input('checkout_shipping_address', []);
        $amount = $request->input('amount');
        
        $customer = StripeCustomer::where('email', $email)->first();
        
        if (!$customer) {
            $stripeCustomer = $this->stripeService->createCustomer([
                'email' => $email,
                'name' => $shipping['first_name'] . ' ' . $shipping['last_name'],
                'phone' => $shipping['phone'] ?? null,
                'address' => [
                    'line1' => $shipping['address1'],
                    'city' => $shipping['city'],
                    'state' => $shipping['province'],
                    'postal_code' => $shipping['zip'],
                    'country' => $shipping['country'] ?? 'US',
                ]
            ]);

            $customer = StripeCustomer::create([
                'stripe_id' => $stripeCustomer->id,
                'email' => $email,
                'first_name' => $shipping['first_name'],
                'last_name' => $shipping['last_name'],
                'checkout_shipping_address_address1' => $shipping['address1'],
                'checkout_shipping_address_city' => $shipping['city'],
                'checkout_shipping_address_province' => $shipping['province'],
                'checkout_shipping_address_zip' => $shipping['zip'],
                'checkout_shipping_address_country' => $shipping['country'] ?? 'US',
            ]);
        }

        return Inertia::render('Payment', [
            'customer_id' => $customer->stripe_id,
            'email' => $email,
            'shipping_address' => $shipping,
            'amount' => $amount,
            'items' => $request->input('items', []), 
            'stripe_key' => config('services.stripe.key'),
        ]);
    }

    public function process(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|string', 
            'email' => 'required|email',
            'amount' => 'required|numeric',
            'items' => 'required|array',
            'shipping_address' => 'required|array',
        ]);

        $customer = StripeCustomer::where('email', $request->email)->firstOrFail();

        try {
            $this->stripeService->getClient()->customers->update($customer->stripe_id, [
                'source' => $request->payment_method_id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => ['message' => $e->getMessage()]], 400);
        }

        $shipping = $request->shipping_address;
        $orderData = [
            'email' => $request->email,
            'fulfillment_status' => 'unfulfilled',
            'line_items' => $request->items,
            'financial_status' => 'authorized',
            'shipping_address' => [
                'first_name' => $shipping['first_name'],
                'last_name' => $shipping['last_name'],
                'address1' => $shipping['address1'],
                'city' => $shipping['city'],
                'province' => $shipping['province'],
                'country' => $shipping['country'],
                'zip' => $shipping['zip'],
                'phone' => $shipping['phone'] ?? '',
            ],
            'billing_address' => [
                'first_name' => $shipping['first_name'],
                'last_name' => $shipping['last_name'],
                'address1' => $shipping['address1'],
                'city' => $shipping['city'],
                'province' => $shipping['province'],
                'country' => $shipping['country'],
                'zip' => $shipping['zip'],
            ],
            'transactions' => [
                [
                    'kind' => 'authorization',
                    'status' => 'success',
                    'amount' => $request->amount,
                ]
            ]
        ];

        $shopifyOrder = $this->shopifyService->createOrder($orderData);

        if (isset($shopifyOrder['errors'])) {
            return response()->json(['error' => ['message' => 'Shopify Order Failed: ' . json_encode($shopifyOrder['errors'])]], 400);
        }

        try {
            $shopifyOrderId = $shopifyOrder['order']['id'] ?? null;
            $metadata = $shopifyOrderId ? ['order_id' => $shopifyOrderId] : [];

            $paymentIntent = $this->stripeService->createPaymentIntent(
                $request->amount * 100, 
                'usd',
                $customer->stripe_id,
                null,
                $metadata
            );
            
        } catch (\Exception $e) {
             return response()->json(['error' => ['message' => 'Payment Failed: ' . $e->getMessage()]], 400);
        }

        return response()->json([
            'success' => true,
            'order' => $shopifyOrder['order'] ?? null,
            'redirect_url' => route('thankyou'),
        ]);
    }
    
    public function thankyou() {
        return Inertia::render('ThankYou');
    }
}

