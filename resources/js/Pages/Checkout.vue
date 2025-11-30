<template>
  <div class="min-h-screen bg-gray-50 flex flex-col md:flex-row">
    <div class="md:w-2/3 p-6 md:p-12 lg:p-16">
      <header class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Bipty</h1>
        <nav class="flex text-sm text-gray-500 mt-4">
          <a href="#" class="hover:text-gray-900">Cart</a>
          <span class="mx-2">&gt;</span>
          <span class="text-gray-900 font-medium">Information</span>
          <span class="mx-2">&gt;</span>
          <span>Payment</span>
        </nav>
      </header>

      <main>
        <form @submit.prevent="submit">
          <section class="mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Contact information</h2>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input v-model="form.email" type="email" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
              <div v-if="errors.email" class="text-red-500 text-sm mt-1">{{ errors.email }}</div>
            </div>
          </section>

          <section class="mb-8">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Shipping address</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First name</label>
                <input v-model="form.shipping_address.first_name" type="text" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last name</label>
                <input v-model="form.shipping_address.last_name" type="text" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
              </div>
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
              <input v-model="form.shipping_address.address1" type="text" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
            </div>
            
            <div class="mb-4">
               <label class="block text-sm font-medium text-gray-700 mb-1">Apartment, suite, etc. (optional)</label>
              <input v-model="form.shipping_address.address2" type="text" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input v-model="form.shipping_address.city" type="text" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                </div>
                 <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <input v-model="form.shipping_address.province" type="text" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                </div>
                 <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ZIP code</label>
                    <input v-model="form.shipping_address.zip" type="text" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                </div>
            </div>
            
             <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
              <select v-model="form.shipping_address.country" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
                  <option value="US">United States</option>
              </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input v-model="form.shipping_address.phone" type="tel" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
            </div>

          </section>

          <div class="flex items-center justify-between mt-8">
             <a :href="returnUrl" class="text-blue-600 hover:text-blue-800 text-sm">&lt; Return to cart</a>
             <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 font-medium">Continue to shipping</button>
          </div>
        </form>
      </main>
    </div>

    <aside class="md:w-1/3 bg-gray-100 p-6 border-l border-gray-200">
      <h2 class="sr-only">Order Summary</h2>
      <div v-for="(item, index) in items" :key="index" class="flex items-center mb-4">
        <div class="relative w-16 h-16 border border-gray-200 rounded overflow-hidden bg-white">
          <img :src="item.image" :alt="item.name" class="w-full h-full object-cover">
          <span class="absolute top-0 right-0 -mt-2 -mr-2 bg-gray-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ item.quantity }}</span>
        </div>
        <div class="ml-4 flex-1">
          <h3 class="text-sm font-medium text-gray-900">{{ item.name }}</h3>
          <p class="text-xs text-gray-500" v-if="item.properties && item.properties.Date">{{ item.properties.Date }}</p>
        </div>
        <div class="text-sm font-medium text-gray-900">${{ formatPrice(item.total) }}</div>
      </div>
      
      <hr class="my-6 border-gray-200">
      
      <div class="mb-6">
          <div class="flex gap-2">
              <input v-model="discountCode" type="text" placeholder="Discount code" class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 p-2 border">
              <button @click="applyDiscount" :disabled="applyingDiscount || !discountCode" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 disabled:opacity-50 font-medium">Apply</button>
          </div>
           <p v-if="discountMessage" :class="{'text-green-600': discountSuccess, 'text-red-600': !discountSuccess}" class="text-sm mt-2">{{ discountMessage }}</p>
      </div>

      <hr class="my-6 border-gray-200">
      
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
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  items: Array,
  amount: [Number, String],
  currency: String,
  returnUrl: String,
});

const form = useForm({
  email: '',
  shipping_address: {
    first_name: '',
    last_name: '',
    address1: '',
    address2: '',
    city: '',
    province: '',
    zip: '',
    country: 'US',
    phone: '',
  },
  amount: props.amount,
  currency: props.currency,
  items: props.items
});

const discountCode = ref('');
const applyingDiscount = ref(false);
const discountMessage = ref('');
const discountSuccess = ref(false);

const submit = () => {
  form.post(route('checkout.store'));
};

const applyDiscount = async () => {
    if (!discountCode.value) return;
    
    applyingDiscount.value = true;
    discountMessage.value = '';
    
    try {
        const productId = props.items.length > 0 ? props.items[0].product_id : null; 
        const response = await axios.post(route('checkout.discount'), {
            code: discountCode.value,
            product_id: productId
        });
        
        if (response.data.status) { 
             discountSuccess.value = true;
             discountMessage.value = 'Discount applied!';
        } else {
             discountSuccess.value = false;
             discountMessage.value = response.data.msg || 'Invalid discount code';
        }

    } catch (error) {
        discountSuccess.value = false;
        discountMessage.value = 'Error applying discount';
        console.error(error);
    } finally {
        applyingDiscount.value = false;
    }
};

const formatPrice = (val) => {
    return parseFloat(val).toFixed(2);
}
</script>
