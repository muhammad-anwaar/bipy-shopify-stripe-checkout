<?php

namespace App\Http\Controllers;

use App\Services\ShopifyService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ApiController extends Controller
{
    protected $stripeService;
    protected $shopifyService;

    public function __construct(StripeService $stripeService, ShopifyService $shopifyService)
    {
        $this->stripeService = $stripeService;
        $this->shopifyService = $shopifyService;
    }

    public function generateToken(Request $request)
    {
        $user = User::firstOrCreate(
            ['email' => 'api@bipty.com'],
            ['name' => 'API User', 'password' => Hash::make(Str::random(16))]
        );

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['status' => true, 'msg' => 'Token successfully Generated', 'token' => $token]);
    }

    protected function getStripeDetails($orderId)
    {
        $result = DB::table('stripe')
            ->join('orders', 'stripe.email', '=', 'orders.email')
            ->where('orders.order_id', $orderId)
            ->select('stripe.*', 'orders.charge_id', 'orders.intent_id')
            ->first();
            
        return $result;
    }

    public function capturePayments(Request $request)
    {
        $orderId = $request->input('order_id');
        $totalPrice = $request->input('order_price');

        if (!$orderId || !is_numeric($totalPrice) || $totalPrice <= 0) {
            return response()->json(['status' => false, 'msg' => 'Invalid input.']);
        }

        $row = $this->getStripeDetails($orderId);

        if (!$row) {
            return response()->json(['status' => false, 'msg' => 'Order Not Found']);
        }

        try {
            $this->stripeService->capturePaymentIntent($row->intent_id);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'msg' => 'Stripe Capture Failed: ' . $e->getMessage()]);
        }

        $transactions = $this->shopifyService->getTransactions($orderId);
        $parentId = $transactions['transactions'][0]['id'] ?? null;

        if ($parentId) {
            $this->shopifyService->createTransaction($orderId, [
                "currency" => "USD",
                "amount" => (string)$totalPrice,
                "kind" => "capture",
                "parent_id" => $parentId
            ]);
        }

        return response()->json(['status' => true, 'msg' => 'payments captured successfully']);
    }

    public function captureDamageLossLateFee(Request $request)
    {
        $orderId = $request->input('order_id');
        $amount = $request->input('amount');

        if (!$orderId || !is_numeric($amount) || $amount <= 0) {
            return response()->json(['status' => false, 'msg' => 'Invalid amount.']);
        }

        $row = $this->getStripeDetails($orderId);

        if (!$row) {
            return response()->json(['status' => false, 'msg' => 'Order Not Found']);
        }

        try {
            $this->stripeService->createCharge(
                $amount * 100,
                'usd',
                $row->stripe_id,
                $row->payment_method_id,
                '#' . $orderId . ' - Additional Charge',
                ["order_id" => $orderId, 'type' => 'Additional Charge']
            );
            return response()->json(['status' => true, 'msg' => 'Amount Captured successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'msg' => 'Error On Amount Captured: ' . $e->getMessage()]);
        }
    }

    public function refund(Request $request)
    {
        $orderId = $request->input('order_id');
        $totalPrice = $request->input('total_price');

        if (!$orderId || !is_numeric($totalPrice) || $totalPrice <= 0) {
            return response()->json(['status' => false, 'msg' => 'Invalid amount.']);
        }

        $row = $this->getStripeDetails($orderId);

        if (!$row) {
            return response()->json(['status' => false, 'msg' => 'Order Not Found In App.']);
        }

        try {
            $chargeId = $row->charge_id;
            
            if ($chargeId) {
                 $charge = $this->stripeService->retrieveCharge($chargeId);
                 if ($charge->captured && $charge->status == 'succeeded') {
                     $this->stripeService->createRefund($chargeId);
                 } elseif (!$charge->captured && $charge->status == 'succeeded') {
                     $this->stripeService->cancelPaymentIntent($row->intent_id);
                 } else {
                      $this->stripeService->createRefund($chargeId);
                 }
            } elseif ($row->intent_id) {
                 $this->stripeService->cancelPaymentIntent($row->intent_id);
            }

            $orderDetails = $this->shopifyService->getOrder($orderId);
            $transactions = $this->shopifyService->getTransactions($orderId);

            $lineItemId = $orderDetails['order']['line_items'][0]['id'];
            $lineItemQty = $orderDetails['order']['line_items'][0]['quantity'];
            $transactionId = $transactions['transactions'][0]['id'];
            $transactionAmount = $transactions['transactions'][0]['amount'];

            $this->shopifyService->refundOrder($orderId, [
                "currency" => "USD",
                "notify" => true,
                "note" => "wrong size",
                "shipping" => ["full_refund" => true],
                "refund_line_items" => [[
                    "line_item_id" => $lineItemId,
                    "quantity" => $lineItemQty,
                    "restock_type" => "no_restock"
                ]],
                "transactions" => [[
                    "parent_id" => $transactionId,
                    "amount" => $transactionAmount,
                    "kind" => "refund",
                    "gateway" => ""
                ]]
            ]);

            return response()->json(['status' => true, 'msg' => 'Order successfully refunded']);

        } catch (\Exception $e) {
             return response()->json(['status' => false, 'msg' => 'Refund Failed: ' . $e->getMessage()]);
        }
    }

    public function cancelRefund(Request $request)
    {
        $orderId = $request->input('order_id');
        $this->refund($request);
        
        $this->shopifyService->cancelOrder($orderId);
        
        return response()->json(['status' => true, 'msg' => 'Order successfully refunded and canceled']);
    }
    
    public function voidOrder(Request $request)
    {
        $orderId = $request->input('order_id');
        $totalPrice = $request->input('total_price');
        
         if (!$orderId || !is_numeric($totalPrice) || $totalPrice <= 0) {
            return response()->json(['status' => false, 'msg' => 'Invalid order details.']);
        }

        $row = $this->getStripeDetails($orderId);
        
        if (!$row) {
             return response()->json(['status' => false, 'msg' => 'Order Not Found In App.']);
        }
        
        $this->shopifyService->cancelOrder($orderId);
        
        $transactions = $this->shopifyService->getTransactions($orderId);
        $transactionId = $transactions['transactions'][0]['id'];
        $transactionAmount = $transactions['transactions'][0]['amount'];
        
        $this->shopifyService->createTransaction($orderId, [
             "currency" => "USD",
             "amount" => $transactionAmount,
             "kind" => "void",
             "parent_id" => $transactionId
        ]);
        
        $chargeId = $row->charge_id;
        if ($chargeId) {
            $charge = $this->stripeService->retrieveCharge($chargeId);
             if ($charge->captured && $charge->status == 'succeeded') {
                 $this->stripeService->createRefund($chargeId);
             } elseif (!$charge->captured && $charge->status == 'succeeded') {
                 $this->stripeService->cancelPaymentIntent($row->intent_id);
             } else {
                  $this->stripeService->createRefund($chargeId);
             }
        }

        return response()->json(['status' => true, 'msg' => 'Order voided successfully']);
    }

    public function partiallyPaid(Request $request)
    {
        $orderId = $request->input('order_id');
        $amount = $request->input('partial_price');
        
        if (!$orderId || !is_numeric($amount) || $amount <= 0) {
            return response()->json(['status' => false, 'msg' => 'Invalid order details.']);
        }
        
        $row = $this->getStripeDetails($orderId);
        
        if ($row) {
            $this->stripeService->capturePaymentIntent($row->intent_id, $amount * 100);
            
            $transactions = $this->shopifyService->getTransactions($orderId);
            $transactionId = $transactions['transactions'][0]['id'];
            
            $this->shopifyService->createTransaction($orderId, [
                "currency" => "USD",
                "amount" => (string)$amount,
                "kind" => "capture",
                "parent_id" => $transactionId
            ]);
            
            return response()->json(['status' => true, 'msg' => 'Partial payments captured successfully']);
        }
        
        return response()->json(['status' => false, 'msg' => 'Order not found']);
    }

    public function partiallyRefund(Request $request)
    {
        $orderId = $request->input('order_id');
        $amount = $request->input('partial_price');
        
        if (!$orderId || !is_numeric($amount) || $amount <= 0) {
            return response()->json(['status' => false, 'msg' => 'Invalid order details.']);
        }
        
        $row = $this->getStripeDetails($orderId);
        
        if ($row) {
            $this->stripeService->createRefund($row->charge_id, $amount * 100);
            
            $transactions = $this->shopifyService->getTransactions($orderId);
            $transactionId = $transactions['transactions'][0]['id'];
            
             $this->shopifyService->createTransaction($orderId, [
                "currency" => "USD",
                "amount" => (string)$amount,
                "kind" => "refund",
                "parent_id" => $transactionId
            ]);
            
            return response()->json(['status' => true, 'msg' => 'Partial refund processed successfully']);
        }
         return response()->json(['status' => false, 'msg' => 'Order not found']);
    }

    public function lenderOnboardingLink(Request $request)
    {
        $email = $request->input('email');
        
        $express = DB::table('stripe_express')->where('email', $email)->first();
        
        if ($express) {
            return response()->json(['status' => true, 'stripe_id' => $express->stripe_account_id, 'onboard_url' => $express->url]);
        }
        
        $customer = $this->stripeService->createExpressAccount($email);
        $customerId = $customer->id;
        
        DB::table('stripe_express')->insert([
            'stripe_account_id' => $customerId,
            'email' => $email
        ]);
        
        $link = $this->stripeService->createAccountLink(
            $customerId,
            url('/onboard/refresh?link_id=' . base64_encode($customerId)),
            url('/onboard/return?link_id=' . base64_encode($customerId))
        );
        
        DB::table('stripe_express')
            ->where('stripe_account_id', $customerId)
            ->update(['url' => $link->url]);
            
        return response()->json(['status' => true, 'stripe_id' => $customerId, 'onboard_url' => $link->url]);
    }
    
    public function lenderPayout(Request $request)
    {
        $accountId = $request->input('connected_stripe_account_id');
        $amount = $request->input('amount');
        
         if (!$accountId || !is_numeric($amount) || $amount <= 0) {
            return response()->json(['status' => false, 'msg' => 'Invalid amount.']);
        }
        
        $exists = DB::table('stripe_express')->where('stripe_account_id', $accountId)->exists();
        
        if (!$exists) {
             return response()->json(['status' => false, 'msg' => 'Stripe account not found.']);
        }
        
        $transfer = $this->stripeService->createTransfer($amount * 100, 'usd', $accountId);
        
        return response()->json(['status' => true, 'result' => $transfer]);
    }
}
