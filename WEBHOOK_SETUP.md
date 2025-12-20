# Webhook Configuration Guide

## Overview
This application uses webhooks to receive delivery reports from the Beem SMS API. For webhooks to work properly in production, you need to configure a publicly accessible URL.

## Current Issue
The application is currently configured to use `http://localhost` as the webhook URL, which won't work in production because:
- External services (like Beem API) cannot reach localhost URLs
- Webhooks need to be accessible from the internet

## Solution

### 1. For Development/Testing
If you're testing locally and need webhooks to work, you can use tools like:
- **ngrok**: `ngrok http 8000` (if using `php artisan serve`)
- **Expose**: `expose 8000`
- **LocalTunnel**: `lt --port 8000`

Then update your `.env` file:
```env
WEBHOOK_URL=https://your-ngrok-url.ngrok.io
```

### 2. For Production
Update your `.env` file with your actual domain:
```env
WEBHOOK_URL=https://yourdomain.com
```

### 3. Environment Variables
The application now supports separate webhook URL configuration:
- `WEBHOOK_URL`: Used specifically for webhook endpoints (recommended)
- `APP_URL`: Falls back to this if WEBHOOK_URL is not set

## Webhook Endpoints
- **Beem DLR**: `{WEBHOOK_URL}/webhooks/beem/dlr`
- **Payment**: `{WEBHOOK_URL}/payments/webhook`
- **Test**: `{WEBHOOK_URL}/webhooks/test`
- **Health**: `{WEBHOOK_URL}/webhooks/health`

## Testing Webhooks
1. Set up a public URL (ngrok, production domain, etc.)
2. Update `WEBHOOK_URL` in your `.env` file
3. Send a test SMS through the application
4. Check the logs to see if delivery reports are received

## Important Notes
- Webhook URLs must be HTTPS in production
- Make sure your server can receive POST requests on the webhook endpoints
- Check firewall settings to ensure webhook endpoints are accessible
- Monitor webhook logs for debugging: `tail -f storage/logs/laravel.log`