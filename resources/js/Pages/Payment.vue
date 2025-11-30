<template>
  <div class="min-h-screen bg-gray-50 flex flex-col md:flex-row">
    <div class="md:w-2/3 p-6 md:p-12 lg:p-16">
         <header class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Bipty</h1>
        <nav class="flex text-sm text-gray-500 mt-4">
          <a href="#" class="hover:text-gray-900">Cart</a>
          <span class="mx-2">&gt;</span>
          <a :href="route('checkout.index')" class="hover:text-gray-900">Information</a>
          <span class="mx-2">&gt;</span>
          <span class="text-gray-900 font-medium">Payment</span>
        </nav>
      </header>

      <main>
          <div class="border border-gray-200 rounded-md bg-white p-4 mb-8 text-sm text-gray-600">
              <div class="flex justify-between border-b border-gray-200 pb-4 mb-4">
                  <div>
                      <span class="text-gray-500 mr-4">Contact</span>
                      <span class="text-gray-900 font-medium">{{ email }}</span>
                  </div>
                  <a href="#" @click.prevent="goBack" class="text-blue-600 hover:underline">Change</a>
              </div>
               <div class="flex justify-between border-b border-gray-200 pb-4 mb-4">
                  <div>
                      <span class="text-gray-500 mr-4">Ship to</span>
                      <span class="text-gray-900 font-medium">
                          {{ shipping_address.address1 }}, {{ shipping_address.city }}, {{ shipping_address.province }} {{ shipping_address.zip }}, {{ shipping_address.country }}
                      </span>
                  </div>
                   <a href="#" @click.prevent="goBack" class="text-blue-600 hover:underline">Change</a>
              </div>
               <div class="flex justify-between">
                  <div>
                      <span class="text-gray-500 mr-4">Method</span>
                      <span class="text-gray-900 font-medium">Standard · Free</span>
                  </div>
              </div>
          </div>

          <section>
               <h2 class="text-lg font-medium text-gray-900 mb-4">Payment</h2>
               <p class="text-sm text-gray-500 mb-4">All transactions are secure and encrypted.</p>
               
               <div class="bg-white border border-gray-200 rounded-md p-4">
                   <div id="card-element" class="p-3 border border-gray-300 rounded-md">
                   </div>
                   <div id="card-errors" role="alert" class="text-red-500 text-sm mt-2"></div>
               </div>

               <div class="mt-8 flex justify-between items-center">
                   <a href="#" @click.prevent="goBack" class="text-blue-600 hover:text-blue-800 text-sm">&lt; Return to shipping</a>
                   <button 
                    @click="handlePayment" 
                    :disabled="processing"
                    class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium disabled:opacity-50 flex items-center">
                       <span v-if="!processing">Pay now</span>
                       <span v-else>Processing...</span>
                   </button>
               </div>
          </section>

      </main>
    </div>

    <aside class="md:w-1/3 bg-gray-100 p-6 border-l border-gray-200">
       <h2 class="sr-only">Order Summary</h2>
       <div class="flex justify-between mb-2 text-sm">
          <span class="text-gray-600">Subtotal</span>
          <span class="font-medium text-gray-900">${{ formatPrice(amount) }}</span>
      </div>
      <div class="flex justify-between mb-2 text-sm">
          <span class="text-gray-600">Shipping</span>
          <span class="font-medium text-gray-900">Free</span>
      </div>
      <hr class="my-6 border-gray-200">
       <div class="flex justify-between text-lg font-bold">
          <span class="text-gray-900">Total</span>
          <span class="text-gray-900">${{ formatPrice(amount) }}</span>
      </div>
    </aside>

  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { loadStripe } from '@stripe/stripe-js';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    customer_id: String,
    email: String,
    shipping_address: Object,
    amount: [Number, String],
    stripe_key: String,
    items: Array
});

const stripe = ref(null);
const card = ref(null);
const processing = ref(false);

onMounted(async () => {
    const stripeInstance = await loadStripe(props.stripe_key);
    stripe.value = stripeInstance;
    const elements = stripeInstance.elements();
    card.value = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#32325d',
            }
        }
    });
    card.value.mount('#card-element');
    
    card.value.on('change', ({error}) => {
        const displayError = document.getElementById('card-errors');
        if (error) {
            displayError.textContent = error.message;
        } else {
            displayError.textContent = '';
        }
    });
});

const handlePayment = async () => {
    processing.value = true;
    
    const { token, error } = await stripe.value.createToken(card.value);

    if (error) {
        const displayError = document.getElementById('card-errors');
        displayError.textContent = error.message;
        processing.value = false;
    } else {
        processOrder(token.id);
    }
};

const processOrder = (paymentMethodId) => {
    axios.post(route('payment.process'), {
        payment_method_id: paymentMethodId,
        email: props.email,
        amount: props.amount,
        items: props.items,
        shipping_address: props.shipping_address
    }).then(response => {
        if (response.data.success) {
            window.location.href = response.data.redirect_url;
        } else {
            alert('Payment failed: ' + JSON.stringify(response.data.error));
            processing.value = false;
        }
    }).catch(error => {
        console.error(error);
         alert('Payment error occurred.');
        processing.value = false;
    });
};

const goBack = () => {
    window.history.back();
}

const formatPrice = (val) => {
    return parseFloat(val).toFixed(2);
}
</script>

