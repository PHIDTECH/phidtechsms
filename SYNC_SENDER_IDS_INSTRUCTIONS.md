# How to Sync Beem Sender IDs to Admin Panel

I've fixed the "Sync from Beem" functionality. Here's how to use it:

## Steps:

1. **Update your .env file** with the correct Beem API credentials (if you haven't already):
   ```env
   BEEM_API_KEY=501e41f128d5a9fe
   BEEM_SECRET_KEY=NmRiZDlhMDM2YWY4YmNhM2NlMWUzNGZjYWRiOGU5YWUyYzgzNTJlMmViMzE5YzFjZDM5ODBjZmYzM2RhZjlmMw==
   ```

2. **Restart your server** (if you updated .env):
   - Press Ctrl+C
   - Run `php artisan serve`

3. **Go to Admin Panel** → **Sender IDs** page

4. **Click "Sync from Beem"** button

5. **Wait** for the sync to complete (it will show a success message)

6. **Refresh the page** - you should now see all your Beem sender IDs including:
   - HARUSI
   - MCHANGO
   - kadijanja
   - LYC
   - TAG-RCC
   - GPIS
   - BABA TJ
   - RAHISITECH
   - TENDERCARE
   - Minnahstore
   - NJUNJU
   - SMARTSALES
   - tonygaming
   - DOLOKA
   - MINJASTORE
   - Vax Grapher
   - And all others from your reseller account

## What I Fixed:

The `syncSenderIds` method was missing required database fields (`use_case` and `sample_messages`), which caused it to fail silently. I've updated it to:
- Include all required fields
- Map Beem's 'active' status to 'approved' in your local database
- Store the Beem sender ID for reference
- Show all sender IDs from your reseller account

These sender IDs will be available to all users when creating campaigns.
