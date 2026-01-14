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

## � Installation & Setup

### 1. Manual Dependency Setup
Since this plugin requires `firebase/php-jwt`, and you have manually installed it:
- Ensure the library is located at: `includes/jwt/src/`
- The plugin automatically detects and loads the library from this path.

### 2. Configuration
- **Settings Page**: Go to **WooCommerce > UCP Settings** to manage the plugin.
- **Debug Mode**: Enable this to log API events and errors (requires `WP_DEBUG`).
- **Development Mode**: Enable this to bypass full JWT verification.
- **Security Limits**: Set a **Max Order Total** to limit financial exposure from AI agents.

### 3. Payment Gateway
- Go to **WooCommerce > Settings > Payments**.
- Ensure **UCP Payment** is enabled.

---

## 🧪 Testing
For a comprehensive guide on how to test the plugin, please refer to:
� **[TESTING.md](file:///c:/Users/Administrator/Documents/GitHub/WooUCP/TESTING.md)**

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
