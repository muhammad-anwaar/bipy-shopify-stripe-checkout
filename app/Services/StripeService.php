<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Charge;
use Stripe\Refund;
use Stripe\Transfer;

class StripeService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function getCustomerByEmail($email)
    {
        return $this->stripe->customers->all(['email' => $email, 'limit' => 1])->data[0] ?? null;
    }

    public function createCustomer($data)
    {
        return $this->stripe->customers->create($data);
    }

    public function createPaymentIntent($amount, $currency, $customerId, $paymentMethodId = null, $metadata = [])
    {
        $params = [
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customerId,
            'confirm' => true,
            'off_session' => true,
            'capture_method' => 'manual',
            'metadata' => $metadata,
        ];

        if ($paymentMethodId) {
            $params['payment_method'] = $paymentMethodId;
        }

        return $this->stripe->paymentIntents->create($params);
    }
    
    public function getClient()
    {
        return $this->stripe;
    }

    public function retrievePaymentIntent($intentId)
    {
        return PaymentIntent::retrieve($intentId);
    }

    public function capturePaymentIntent($intentId, $amount = null)
    {
        $intent = PaymentIntent::retrieve($intentId);
        if ($amount) {
             return $intent->capture(['amount_to_capture' => $amount]);
        }
        return $intent->capture();
    }

    public function createCharge($amount, $currency, $customerId, $paymentMethodId, $description = '', $metadata = [])
    {
        return PaymentIntent::create([
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customerId,
            'payment_method' => $paymentMethodId,
            'off_session' => true,
            'confirm' => true,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public function retrieveCharge($chargeId)
    {
        return Charge::retrieve($chargeId);
    }

    public function createRefund($chargeId, $amount = null)
    {
        $params = ['charge' => $chargeId];
        if ($amount) {
            $params['amount'] = $amount;
        }
        return Refund::create($params);
    }

    public function cancelPaymentIntent($intentId)
    {
        $intent = PaymentIntent::retrieve($intentId);
        return $intent->cancel();
    }

    public function createExpressAccount($email)
    {
        return $this->stripe->accounts->create([
            'type' => 'express',
            'country' => 'US',
            'email' => $email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'individual',
        ]);
    }

    public function createAccountLink($accountId, $refreshUrl, $returnUrl)
    {
        return $this->stripe->accountLinks->create([
            'account' => $accountId,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);
    }

    public function createTransfer($amount, $currency, $destinationId)
    {
        return Transfer::create([
            "amount" => $amount,
            "currency" => $currency,
            "destination" => $destinationId,
        ]);
    }
}
