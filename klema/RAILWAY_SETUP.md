# Railway Deployment - Email Setup Guide

## 🔴 Critical Issues to Fix

The 9-10 second delays mean emails are still blocking. Here's what to check:

### 1. Queue Connection (MUST BE SET)
**Set this environment variable on Railway:**
```env
QUEUE_CONNECTION=database
```

**If this is NOT set or set to `sync`, emails will run immediately and block!**

### 2. Queue Worker (MUST BE RUNNING)
**You NEED to run a queue worker on Railway:**

**Option A: Add a separate Railway service (Recommended)**
1. Go to Railway dashboard
2. Add a new service
3. Select your existing source
4. Set the start command to: `php artisan queue:work --tries=3 --timeout=60`
5. Make sure it has access to the same database

**Option B: Use Railway's process manager (if available)**
- Configure Railway to run multiple processes in the same service
- One process: your web app
- Another process: `php artisan queue:work --tries=3 --timeout=60`

**Without a queue worker running, emails will sit in the queue and never be sent!**

### 3. Email Configuration

**For Resend (Recommended):**
```env
MAIL_MAILER=resend
RESEND_KEY=your_resend_api_key_here
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="KLEMA"
MAIL_TIMEOUT=5
```

**Get Resend API key:**
1. Sign up at https://resend.com
2. Go to API Keys
3. Create a new API key
4. Add it to Railway as `RESEND_KEY`

### 4. Required Environment Variables Checklist

✅ **Must have:**
- `QUEUE_CONNECTION=database` ← **CRITICAL - Without this, emails block!**
- `MAIL_MAILER=resend` (or postmark, ses)
- `RESEND_KEY=your_key` (if using Resend)
- `MAIL_FROM_ADDRESS=your@email.com`
- `MAIL_FROM_NAME="KLEMA"`
- `MAIL_TIMEOUT=5`

## 🧪 How to Test

1. **Check if queue is working:**
   - Register a new user
   - Response should be FAST (< 1 second)
   - Check Railway logs for queue worker processing emails

2. **Check if emails are queued:**
   - Go to your database
   - Check the `jobs` table
   - Should see queued email jobs

3. **Check if queue worker is running:**
   - Check Railway logs
   - Should see: "Processing jobs..."
   - Should see: "Processed: App\Notifications\QueuedVerifyEmail"

## 🐛 Troubleshooting

### Still getting 9-10 second delays?
- ✅ Check `QUEUE_CONNECTION` is `database` (NOT `sync`)
- ✅ Check queue worker is running (check Railway logs)
- ✅ Check Resend is configured correctly
- ✅ Check database connection (queue uses database)

### Emails not sending?
- ✅ Check queue worker is running
- ✅ Check `jobs` table in database (are jobs queued?)
- ✅ Check `failed_jobs` table (are jobs failing?)
- ✅ Check Resend API key is correct
- ✅ Check `MAIL_FROM_ADDRESS` is verified in Resend

### Queue worker keeps crashing?
- ✅ Check database connection
- ✅ Check memory limits
- ✅ Try: `php artisan queue:work --tries=3 --timeout=60 --max-jobs=1000`

## 📝 Railway Service Setup

**Main Web Service:**
- Command: (your existing command, probably `php artisan serve` or similar)
- Environment variables: (all your app variables)

**Queue Worker Service:**
- Command: `php artisan queue:work --tries=3 --timeout=60`
- Environment variables: (same as main service - needs DB access)
- Make sure it has the same environment variables as your main service

## 🔗 Quick Links

- Resend Dashboard: https://resend.com/api-keys
- Railway Docs: https://docs.railway.app
- Laravel Queues: https://laravel.com/docs/queues

