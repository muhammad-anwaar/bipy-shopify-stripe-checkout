<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeCustomer extends Model
{
    use HasFactory;

    protected $table = 'stripe';

    protected $fillable = [
        'stripe_id',
        'email',
        'first_name',
        'last_name',
        'checkout_shipping_address_address1',
        'checkout_shipping_address_city',
        'checkout_shipping_address_province',
        'checkout_shipping_address_zip',
        'checkout_shipping_address_country',
    ];
}

