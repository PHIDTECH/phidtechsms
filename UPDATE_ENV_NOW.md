# CRITICAL: Update Your .env File

Your campaign failed because the `.env` file still has the OLD API credentials.

## Step 1: Open .env file
Location: `c:\xampp\htdocs\phidsms\.env`

## Step 2: Find and replace these lines:

```env
BEEM_API_KEY=501e41f128d5a9fe
BEEM_SECRET_KEY=NmRiZDlhMDM2YWY4YmNhM2NlMWUzNGZjYWRiOGU5YWUyYzgzNTJlMmViMzE5YzFjZDM5ODBjZmYzM2RhZjlmMw==
BEEM_DEFAULT_SENDER_ID=RodLine
```

## Step 3: Save the file

## Step 4: Restart your server
- Press `Ctrl+C` in the terminal running `php artisan serve`
- Run `php artisan serve` again

## Step 5: Create a new campaign

The SMS will then be delivered!

---

**Why it failed:**
- Error code 111: "Invalid Sender Id"
- The old API credentials don't have access to STORYZETU
- The new credentials (501e41f128d5a9fe) have access to RodLine, STORYZETU, and 11 other active sender IDs
