# Bipty Shopify Stripe Checkout

This is a Laravel-based application designed to handle custom checkout processes, payment processing via Stripe, and seamless order synchronization with Shopify. It utilizes Vue.js for the frontend and Laravel Sanctum for secure API authentication.

## 🚀 Technology Stack

- **Backend:** [Laravel](https://laravel.com/)
- **Frontend:** [Vue.js](https://vuejs.org/)
- **Authentication:** [Laravel Sanctum](https://laravel.com/docs/sanctum)
- **Payment Gateway:** [Stripe API](https://stripe.com/docs/api) (Payment Intents, Connect, Transfers)
- **E-commerce Integration:** [Shopify Admin API](https://shopify.dev/docs/api/admin)

## ✨ Key Features

### 💳 Payment & Checkout
- **Custom Checkout Flow:** Specialized checkout pages tailored for specific business logic (`Checkout.vue`, `Payment.vue`).
- **Payment Processing:** Secure payment handling using Stripe Payment Intents.
- **Manual Capture:** Support for authorizing payments first and capturing them later (e.g., after order fulfillment).

### 🔄 Shopify Integration
- **Order Synchronization:** Automatically syncs payment statuses and transactions back to Shopify.
- **Transaction Management:** Records captures, refunds, and voids in Shopify to keep order history accurate.
- **Order Actions:** Ability to cancel or refund orders directly within Shopify via API.

### 🛠 Order Management API
- **Capture Payments:** finalize authorized payments.
- **Damage & Late Fees:** Specific endpoints to charge customers for damages, losses, or late returns (`capture-damage-loss-late-fee`).
- **Refunds:** Support for both full and partial refunds, including partial payment captures.
- **Void Orders:** Cancel orders and release payment authorizations.

### 🤝 Marketplace / Lender Features
- **Lender Onboarding:** Generate onboarding links for lenders to connect their Stripe Express accounts (`lender-onboarding-link`).
- **Payouts:** Automated transfers to lender Stripe accounts (`lender-payout`).

## 📂 Project Structure

- **`app/Http/Controllers/ApiController.php`**: Core logic for handling API requests (payments, refunds, fees).
- **`app/Services/StripeService.php`**: Wrapper service for Stripe API interactions.
- **`app/Services/ShopifyService.php`**: Wrapper service for Shopify Admin API interactions.
- **`resources/js/Pages`**: Vue.js components for the frontend checkout flow.

## ⚙️ Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd bipy-shopify-stripe-checkout
   ```

2. **Install Backend Dependencies:**
   ```bash
   composer install
   ```

3. **Install Frontend Dependencies:**
   ```bash
   npm install
   ```

4. **Environment Configuration:**
   Copy `.env.example` to `.env` and configure your credentials:
   ```bash
   cp .env.example .env
   ```
   Update the following variables in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   # .. database credentials ..

   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...

   SHOPIFY_URL=https://your-shop.myshopify.com
   SHOPIFY_TOKEN=shpat_...
   ```

5. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

6. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

7. **Build Frontend Assets:**
   ```bash
   npm run dev
   ```

8. **Serve the Application:**
   ```bash
   php artisan serve
   ```

## 🔒 API Authentication

The application uses Laravel Sanctum for API authentication.
- **Generate Token:** Send a POST request to `/api/generate-token` to receive a bearer token.
- **Authenticated Requests:** Include the token in the `Authorization` header (`Bearer <token>`) for all protected endpoints.

## 📄 License

[MIT license](https://opensource.org/licenses/MIT).

