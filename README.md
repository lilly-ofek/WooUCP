# WooUCP: Universal Commerce Protocol for WooCommerce

This plugin enables WooCommerce stores to support the Universal Commerce Protocol (UCP). It allows AI agents to discover products, get shipping rates, and create checkout sessions securely.

---

## 🚀 Key Features

- **UCP Discovery**: Standardized manifest at `/.well-known/ucp`.
- **Intelligent Checkout**: Support for shipping rate calculation and secure order creation.
- **Idempotency Protection**: Prevents duplicate orders from AI agents using `idempotency-key` tracking.
- **UCP Payment Gateway**: A dedicated WooCommerce payment method for AI-processed transactions.
- **Security First**: Cryptographic JWS/JWK signature verification.

---
### 1. Key Features
- **UCP Discovery**: Manifest available at `/.well-known/ucp`.
- **Product Discovery**: Clean, AI-friendly product feed at `/wp-json/ucp/v1/products`.
- **Secure Checkout**: Signature verification (JWT) with dynamic key discovery.
- **Idempotency**: Prevents double orders using a unique key system.
- **Agent Insights**: View AI agent details directly in the WooCommerce Order Edit page.
- **Security Controls**: Agent Whitelist, Maximum Order Total limit, and Capability Toggles.
- **Advanced Flow**: Support for WooCommerce **Coupons** and stock validation.

### 2. Configuration
- **Settings Page**: Go to **WooCommerce > UCP Settings** (or click "Settings" on the Plugins page).
- **Security Limits**: Set a **Max Order Total** and an **Agent Whitelist** for safety.
- **Capability Toggles**: Enable only what you need (Checkout/Discovery) for better performance.
- **Debug Mode**: Detailed logging to `debug.log`.

### 3. Payment Gateway
- Go to **WooCommerce > Settings > Payments`.
- Enable the **UCP Payment** gateway to allow the plugin to process AI orders.

### 4. Testing
Refer to [TESTING.md](file:///c:/Users/Administrator/Documents/GitHub/WooUCP/TESTING.md) for detailed cURL examples for discovery, shipping rates, and checkout.

### Quick Discovery Check
```bash
curl -X GET "https://your-site.com/.well-known/ucp"
```

---

## 📜 API Reference

### 1. Discovery Manifest
`GET /.well-known/ucp`

### 2. Shipping Rates
`POST /wp-json/ucp/v1/shipping-rates`

### 3. Create Checkout Session
`POST /wp-json/ucp/v1/checkout-sessions`
- **Headers Required (Production)**:
  - `UCP-Agent`: `profile="[URL]"`
  - `request-signature`: `[JWT]`
  - `idempotency-key`: `[Unique String]`

---

## 📝 Logging & Debugging
The plugin logs all major events if `WP_DEBUG` is enabled. Logs are prefixed with `WooUCP:`.
You can find them in `wp-content/debug.log`.
