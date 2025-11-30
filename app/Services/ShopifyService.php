<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ShopifyService
{
    protected $shopUrl;
    protected $accessToken;

    public function __construct()
    {
        $this->shopUrl = config('services.shopify.url'); 
        $this->accessToken = config('services.shopify.token');
    }

    public function createOrder(array $data)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post("{$this->shopUrl}/admin/api/2023-01/orders.json", [
            'order' => $data
        ]);

        return $response->json();
    }

    public function applyDiscount($code, $productId)
    {
        $url = "{$this->shopUrl}/api/functions/product-discounts/discount-codes/{$code}/apply";

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'product_id' => $productId
        ]);

        return $response->json();
    }

    public function getOrder($orderId)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
        ])->get("{$this->shopUrl}/admin/api/2023-01/orders/{$orderId}.json");

        return $response->json();
    }

    public function getTransactions($orderId)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
        ])->get("{$this->shopUrl}/admin/api/2023-01/orders/{$orderId}/transactions.json");

        return $response->json();
    }

    public function createTransaction($orderId, $data)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post("{$this->shopUrl}/admin/api/2023-01/orders/{$orderId}/transactions.json", [
            'transaction' => $data
        ]);

        return $response->json();
    }

    public function refundOrder($orderId, $data)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post("{$this->shopUrl}/admin/api/2023-01/orders/{$orderId}/refunds.json", [
            'refund' => $data
        ]);

        return $response->json();
    }

    public function cancelOrder($orderId)
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post("{$this->shopUrl}/admin/api/2023-01/orders/{$orderId}/cancel.json", new \stdClass());

        return $response->json();
    }
}
