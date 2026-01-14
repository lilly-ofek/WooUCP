# WooUCP: Testing & Verification Guide

This guide explains how to verify that your Universal Commerce Protocol (UCP) plugin is working correctly on your WooCommerce site.

## 1. Prerequisites
- **WooCommerce** must be active.
- **WP_DEBUG** should be set to `true` in `wp-config.php` to see logs in `/wp-content/debug.log`.
- **UCP for WooCommerce** plugin must be active.

## 2. Verify Discovery
The discovery manifest is the main entry point for AI agents.

### Test via Browser
Visit: `https://your-site.com/.well-known/ucp`
You should see a JSON manifest. If you get a 404, go to **Settings > Permalinks** and just click **Save Changes** to flush the rewrite rules.

### Test via cURL
```bash
curl -I https://your-site.com/.well-known/ucp
```
*Expected: HTTP 200 OK and `Content-Type: application/json`.*

---

## 3. Verify Shipping Rates
Agents ask for shipping options before finalizing the order.

### Test via cURL
```bash
curl -X POST "https://your-site.com/wp-json/ucp/v1/shipping-rates" \
  -H "Content-Type: application/json" \
  -d '{"items": [{"item": {"id": 123}, "quantity": 1}], "currency": "USD"}'
```
*Note: Replace `123` with a real Product ID from your store.*

---

## 4. Verify Checkout (Production Ready)
Since we are in **Production Mode**, you need to provide the `UCP-Agent` header. 

### Test via cURL (Simulated Agent)
1. Ensure **Development (Bypass) Mode** is enabled in **WooCommerce > UCP Settings**.
2. Run the following command:
```bash
curl -X POST "https://your-site.com/wp-json/ucp/v1/checkout-sessions" \
  -H "Content-Type: application/json" \
  -H "UCP-Agent: profile=\"https://mock-agent.com/profile\"" \
  -H "request-signature: test" \
  -H "idempotency-key: unique-test-key-001" \
  -d '{"line_items":[{"item":{"id":123},"quantity":1}],"buyer":{"full_name":"John Doe","email":"john@example.com"},"currency":"USD"}'
```

*To test with full JWT verification, ensure Development Mode is **OFF**.*

---

## 5. Troubleshooting
If things aren't working as expected:
1. **Check Logs**: Look at `/wp-content/debug.log`. The plugin logs all major events starting with `WooUCP:`.
2. **Permalinks**: If endpoints return 404, re-save your permalink settings.
3. **Product IDs**: Ensure the Product IDs you are sending in the request exist in your WooCommerce store.
4. **JWT Library**: Ensure `includes/jwt/src/` contains the extracted files from `firebase/php-jwt`.
