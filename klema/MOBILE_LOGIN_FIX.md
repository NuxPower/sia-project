# Fix 419 Error - Mobile Login

## The Issue
The mobile app is trying to use the web route `/login` which requires CSRF tokens. Mobile apps should use the API route with Bearer tokens.

## Solution

### 1. Use API Route for Login

The mobile app should use:
- **API Route**: `POST /api/auth/login` (uses Bearer tokens, no CSRF needed)
- **NOT**: `POST /login` (web route, requires CSRF)

### 2. Login Request Format

```javascript
// POST to /api/auth/login
const response = await fetch('http://192.168.1.18:8000/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password',
    device_name: 'My Android Phone'
  })
});

const data = await response.json();
// Returns: { success: true, token: 'klema_...', user: {...} }
```

### 3. Use Token for Subsequent Requests

```javascript
// Store the token
localStorage.setItem('klema.apiToken', data.token);

// Use in all API requests
fetch('http://192.168.1.18:8000/api/weather/current', {
  headers: {
    'Authorization': `Bearer ${data.token}`,
    'Accept': 'application/json'
  }
});
```

### 4. Check Your Frontend Code

Make sure your login component:
- Uses `/api/auth/login` for mobile apps
- Stores the Bearer token
- Includes `Authorization: Bearer <token>` header in all API requests

### 5. Clear Config Cache

```bash
php artisan config:clear
```

### 6. Rebuild App

```bash
npm run build:mobile
cd android
./gradlew assembleDebug
adb install app/build/outputs/apk/debug/app-debug.apk
```

## Summary

- ✅ Use `/api/auth/login` (API route)
- ✅ Store the Bearer token
- ✅ Include token in Authorization header
- ❌ Don't use `/login` (web route) for mobile






