# Fix 419 Page Expired Error on Mobile

## The Problem
The mobile app is getting a 419 error when trying to log in. This happens because:
1. The app is trying to access web routes that require CSRF tokens
2. Mobile apps should use API routes with Bearer tokens instead

## Solution

### Option 1: Use API Route for Login (Recommended)

The mobile app should use `/api/auth/login` instead of `/login`. The API route uses Sanctum tokens and doesn't require CSRF.

**For login, use:**
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password",
  "device_name": "My Phone"
}
```

**Response:**
```json
{
  "success": true,
  "token": "klema_...",
  "token_type": "Bearer",
  "user": { ... }
}
```

**Then use the token in subsequent requests:**
```
Authorization: Bearer klema_...
```

### Option 2: Configure CSRF for Mobile (If using web routes)

If you must use web routes, you need to:

1. **Get CSRF cookie first:**
   ```
   GET /sanctum/csrf-cookie
   ```

2. **Then make login request with the cookie**

3. **Update Sanctum config** (already done - added `capacitor://localhost`)

### Quick Fix Steps

1. **Clear Laravel config cache:**
   ```bash
   php artisan config:clear
   ```

2. **Make sure your frontend uses `/api/auth/login` for mobile**

3. **Store the Bearer token and use it for all API requests**

4. **Rebuild and reinstall the app:**
   ```bash
   npm run build:mobile
   cd android
   ./gradlew assembleDebug
   adb install app/build/outputs/apk/debug/app-debug.apk
   ```

## Check Your Frontend Code

Make sure your login component uses:
- `/api/auth/login` (API route) - for mobile
- `/login` (web route) - for web browser only

The API route returns a Bearer token that should be stored and used for all subsequent API requests.






