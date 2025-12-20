# Beem SMS API Integration Guide

This guide explains how to set up and use the Beem SMS API integration for reseller account management.

## Overview

The application now supports API-based interaction with Beem Africa's SMS service, replacing the previous web scraping approach with a more reliable and efficient API integration.

## Features

✅ **API-Based Balance Retrieval** - Get real-time balance from Beem's REST API  
✅ **Automatic Balance Synchronization** - Scheduled hourly balance updates  
✅ **Interactive Configuration** - Easy setup commands for API credentials  
✅ **Connection Testing** - Verify API connectivity and functionality  
✅ **Error Handling** - Robust error handling with fallback mechanisms  
✅ **Rate Limiting** - Built-in rate limiting and retry logic  

## Setup Instructions

### 1. Configure API Credentials

Run the interactive configuration command:

```bash
php artisan beem:configure-api
```

This command will prompt you for:
- **API Key**: Your Beem SMS API key
- **Secret Key**: Your Beem SMS secret key
- **Base URL**: API base URL (default: https://apisms.beem.africa/v1)
- **Sender ID**: Default sender ID for SMS messages

### 2. Test API Connection

Verify your configuration works:

```bash
# Test basic connectivity
php artisan beem:test-api

# Test with balance retrieval
php artisan beem:test-api --balance
```

### 3. Manual Balance Synchronization

Sync balance manually:

```bash
php artisan beem:sync-balance
```

## Available Commands

| Command | Description |
|---------|-------------|
| `beem:configure-api` | Interactive API credentials setup |
| `beem:test-api` | Test API connectivity and functionality |
| `beem:test-api --balance` | Test API connection with balance retrieval |
| `beem:sync-balance` | Manually synchronize balance from API |
| `beem:configure-dashboard` | Legacy dashboard credentials setup |
| `beem:test-credentials` | Legacy credential testing |

## API Endpoints

The integration attempts to retrieve balance from these endpoints in order:

1. `/balance` - Primary balance endpoint
2. `/account/balance` - Alternative balance endpoint
3. `/account` - Account information endpoint
4. `/wallet/balance` - Wallet balance endpoint

## Configuration Files

### Environment Variables (.env)

```env
# Beem SMS API Configuration
BEEM_API_KEY=your_beem_api_key_here
BEEM_SECRET_KEY=your_beem_secret_key_here
BEEM_BASE_URL=https://apisms.beem.africa/v1
BEEM_DEFAULT_SENDER_ID=Phidtech SMS
```

### Service Configuration (config/services.php)

```php
'beem' => [
    'api_key' => env('BEEM_API_KEY'),
    'secret_key' => env('BEEM_SECRET_KEY'),
    'base_url' => env('BEEM_BASE_URL', 'https://apisms.beem.africa/v1'),
    'default_sender_id' => env('BEEM_DEFAULT_SENDER_ID', 'RodLine'),
],
```

## Automatic Scheduling

The system automatically synchronizes balance every hour via Laravel's task scheduler:

```php
// In app/Console/Kernel.php
$schedule->command('beem:sync-balance')
         ->hourly()
         ->withoutOverlapping()
         ->runInBackground();
```

Ensure your cron job is configured:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## API Authentication

The integration uses HTTP Basic Authentication:

```
Authorization: Basic base64(api_key:secret_key)
```

## Error Handling

The system includes comprehensive error handling:

- **Missing Credentials**: Clear error messages when API keys are not configured
- **Network Errors**: Retry logic for temporary network issues
- **API Errors**: Graceful handling of API response errors
- **Fallback Mechanisms**: Falls back to cached balance when API is unavailable

## Troubleshooting

### Common Issues

1. **"API credentials not configured"**
   - Run `php artisan beem:configure-api` to set up credentials
   - Verify `.env` file contains correct `BEEM_API_KEY` and `BEEM_SECRET_KEY`

2. **"Connection failed"**
   - Check internet connectivity
   - Verify API base URL is correct
   - Ensure API credentials are valid

3. **"Balance retrieval failed"**
   - This may be normal if Beem doesn't provide a balance endpoint
   - The system will attempt multiple endpoint variations
   - Check logs for detailed error information

### Debug Mode

Enable debug logging by setting `LOG_LEVEL=debug` in your `.env` file to see detailed API request/response information.

## Migration from Web Scraping

If you were using the previous web scraping approach:

1. The old `retrieveBeemBalance()` method now redirects to the new API method
2. Dashboard credentials are no longer required for balance retrieval
3. All existing functionality remains compatible
4. The new API approach is more reliable and faster

## Security Notes

- API credentials are stored securely in environment variables
- Never commit API keys to version control
- Use HTTPS for all API communications
- Regularly rotate your API credentials

## Support

For issues related to:
- **API Integration**: Check this documentation and run diagnostic commands
- **Beem API**: Contact Beem Africa support
- **Application Issues**: Check application logs and error messages
