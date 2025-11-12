# Route Separation Summary

## Overview
Routes have been properly separated by role according to `full_role_based_feature_access.md`.

## Route Structure

### 1. SHARED ROUTES (Both Admin and Farmer)
**Middleware**: `auth:sanctum, verified, role:admin,farmer`

#### Weather Endpoints (Filtered by farm ownership)
- `GET /api/weather/current` - View current weather
- `GET /api/weather/forecast` - View weather forecast
- `GET /api/weather/history` - View weather history
- `GET /api/weather/historical/{date}` - View historical weather

#### Farm Endpoints (Filtered by ownership)
- `GET /api/farms` - List farms (farmers: own farms, admins: all farms)
- `GET /api/farms/{farm}` - View farm (farmers: own farms, admins: all farms)
- `PUT/PATCH /api/farms/{farm}` - Update farm (farmers: own farms, admins: all farms)
- `DELETE /api/farms/{farm}` - Delete farm (farmers: own farms, admins: all farms)
- `GET /api/farms/{farm}/weather` - View farm weather
- `GET /api/map/farms` - View farms on map (farmers: own farms, admins: all farms)

#### Alert Endpoints (Filtered by farm ownership)
- `GET /api/alerts` - List alerts (farmers: own farms' alerts, admins: all alerts)
- `GET /api/alerts/{alert}` - View alert (farmers: own farms' alerts, admins: all alerts)
- `PUT/PATCH /api/alerts/{alert}` - Update alert (farmers: own farms' alerts, admins: all alerts)
- `DELETE /api/alerts/{alert}` - Delete alert (farmers: own farms' alerts, admins: all alerts)
- `PATCH /api/alerts/{alert}/resolve` - Resolve alert (farmers: own farms' alerts, admins: all alerts)
- `GET /api/alerts/active` - Get active alerts (filtered by role)
- `GET /api/alerts/forecast-warnings` - Get forecast warnings (filtered by role)

#### Activity Endpoints (Filtered by ownership)
- `GET /api/activities` - List activities (farmers: own activities, admins: all activities)

#### Export Endpoints (Filtered by ownership)
- `GET /api/exports` - List exports (farmers: own exports, admins: all exports)
- `POST /api/exports/weather` - Export weather data (farmers: own farms, admins: all farms)
- `POST /api/exports/farms` - Export farm data (farmers: own farms, admins: all farms)
- `POST /api/exports/activities` - Export activity data (farmers: own activities, admins: all activities)
- `GET /api/exports/{export}/download` - Download export (farmers: own exports, admins: all exports)

---

### 2. FARMER-ONLY ROUTES
**Middleware**: `auth:sanctum, verified, role:farmer`

#### Farm Creation
- `POST /api/farms` - Create farm (farmers only)

#### Farm Points
- `POST /api/farms/{farm}/points` - Add farm point (farmers only, to their own farms)

#### Activity Creation
- `POST /api/activities` - Create activity (farmers only)

---

### 3. ADMIN-ONLY ROUTES
**Middleware**: `auth:sanctum, verified, role:admin`

#### Alert Creation
- `POST /api/alerts` - Create alert (admin only)

#### System Management
- `POST /api/admin/weather/update` - Manual weather update (admin only)
- `GET /api/admin/stats` - Get system statistics (admin only)
- `GET /api/admin/farmers` - Get all farmers with farms (admin only)

#### User Management
- `GET /api/admin/users` - List users (admin only)
- `POST /api/admin/users` - Create user (admin only)
- `GET /api/admin/users/{user}` - View user (admin only)
- `PUT/PATCH /api/admin/users/{user}` - Update user (admin only)
- `DELETE /api/admin/users/{user}` - Delete user (admin only)
- `POST /api/admin/users/{user}/reset-password` - Reset password (admin only)
- `GET /api/admin/users/{user}/sessions` - Get user sessions (admin only)
- `DELETE /api/admin/users/{user}/sessions` - Revoke user sessions (admin only)

---

## Web Routes Separation

### 1. SHARED ROUTES (Both Admin and Farmer)
**Middleware**: `auth, verified`

#### Dashboard
- `GET /dashboard` - Dashboard (data filtered by role)
- `GET /calendar` - Calendar (farmers: own alerts, admins: all alerts)

#### Farm Management (Filtered by ownership)
- `GET /farms` - List farms
- `GET /farms/{farm}` - View farm
- `GET /farms/{farm}/edit` - Edit farm form
- `PUT/PATCH /farms/{farm}` - Update farm
- `DELETE /farms/{farm}` - Delete farm

#### Alert Management (Filtered by farm ownership)
- `GET /alerts` - List alerts
- `PATCH /alerts/{alert}/resolve` - Resolve alert
- `DELETE /alerts/{alert}` - Delete alert

#### Activity Management (Filtered by ownership)
- `GET /activities` - List activities
- `GET /activities/{activity}` - View activity
- `GET /activities/{activity}/edit` - Edit activity form
- `PUT/PATCH /activities/{activity}` - Update activity
- `DELETE /activities/{activity}` - Delete activity
- `GET /activities/{activity}/suitability` - Check weather suitability

#### Export Routes (Filtered by ownership)
- `GET /exports` - List exports
- `POST /exports/weather` - Export weather data
- `POST /exports/farms` - Export farm data
- `POST /exports/activities` - Export activity data
- `GET /exports/{export}/download` - Download export

---

### 2. FARMER-ONLY ROUTES
**Middleware**: `auth, verified, role:farmer`

#### Farm Creation
- `GET /farms/create` - Create farm form (farmers only)
- `POST /farms` - Create farm (farmers only)

#### Farm Points
- `POST /farms/{farm}/points` - Add farm point (farmers only)

#### Activity Creation
- `GET /activities/create` - Create activity form (farmers only)
- `POST /activities` - Create activity (farmers only)

---

### 3. ADMIN-ONLY ROUTES
**Middleware**: `auth, verified, role:admin`

#### Alert Creation
- `POST /alerts` - Create alert (admin only)

#### User Management
- `GET /admin/users` - List users (admin only)
- `GET /admin/users/{user}` - View user (admin only)
- `PUT/PATCH /admin/users/{user}` - Update user (admin only)
- `DELETE /admin/users/{user}` - Delete user (admin only)
- `POST /admin/users/{user}/reset-password` - Reset password (admin only)
- `GET /admin/users/{user}/sessions` - Get user sessions (admin only)
- `DELETE /admin/users/{user}/sessions` - Revoke user sessions (admin only)

---

## Key Separations

### ✅ Farm Creation
- **Farmers**: Can create farms (`POST /api/farms`, `POST /farms`)
- **Admins**: Cannot create farms (restricted by middleware and policy)

### ✅ Alert Creation
- **Admins**: Can create alerts (`POST /api/alerts`, `POST /alerts`)
- **Farmers**: Cannot create alerts (restricted by middleware and policy)

### ✅ Activity Creation
- **Farmers**: Can create activities (`POST /api/activities`, `POST /activities`)
- **Admins**: Cannot create activities (restricted by middleware and policy)

### ✅ Farm Points
- **Farmers**: Can add farm points (`POST /api/farms/{farm}/points`, `POST /farms/{farm}/points`)
- **Admins**: Cannot add farm points (restricted by middleware)

### ✅ User Management
- **Admins**: Can manage users (all user management routes)
- **Farmers**: Cannot access user management (restricted by middleware)

### ✅ System Management
- **Admins**: Can access system statistics, manual weather updates, view all farmers
- **Farmers**: Cannot access system management (restricted by middleware)

---

## Data Filtering

### Farms
- **Farmers**: See only their own farms
- **Admins**: See all farms

### Alerts
- **Farmers**: See only alerts for their farms
- **Admins**: See all alerts

### Activities
- **Farmers**: See only their own activities
- **Admins**: See all activities

### Exports
- **Farmers**: Export only their own data
- **Admins**: Export all data

### Weather Data
- **Farmers**: See weather for their farms only
- **Admins**: See weather for all farms

### Map Data
- **Farmers**: See their farms on map
- **Admins**: See all farms on map

---

## Security

### Middleware Protection
- ✅ All routes protected with authentication middleware
- ✅ All routes protected with email verification middleware
- ✅ Role-specific routes protected with role middleware
- ✅ Policies enforce ownership checks at controller level

### Policy Protection
- ✅ `FarmPolicy::create()` - Only farmers can create farms
- ✅ `AlertPolicy::create()` - Only admins can create alerts
- ✅ `ActivityPolicy::create()` - Only farmers can create activities
- ✅ All policies check ownership for update/delete operations
- ✅ Admins can bypass ownership checks for viewing/managing

---

## Testing Checklist

### Farmer Access
- [ ] Farmer can create farms
- [ ] Farmer can view own farms
- [ ] Farmer can update own farms
- [ ] Farmer can delete own farms
- [ ] Farmer can add farm points to own farms
- [ ] Farmer CANNOT create alerts
- [ ] Farmer can view alerts for own farms
- [ ] Farmer can resolve alerts for own farms
- [ ] Farmer can delete alerts for own farms
- [ ] Farmer can create activities
- [ ] Farmer can view own activities
- [ ] Farmer can update own activities
- [ ] Farmer can delete own activities
- [ ] Farmer can export own data
- [ ] Farmer CANNOT access admin endpoints
- [ ] Farmer CANNOT view other users' data

### Admin Access
- [ ] Admin CANNOT create farms
- [ ] Admin can view all farms
- [ ] Admin can update any farm
- [ ] Admin can delete any farm
- [ ] Admin CANNOT add farm points
- [ ] Admin can create alerts
- [ ] Admin can view all alerts
- [ ] Admin can update any alert
- [ ] Admin can delete any alert
- [ ] Admin CANNOT create activities
- [ ] Admin can view all activities
- [ ] Admin can update any activity
- [ ] Admin can delete any activity
- [ ] Admin can export all data
- [ ] Admin can access system statistics
- [ ] Admin can manually trigger weather updates
- [ ] Admin can manage users

---

## Files Modified

1. `routes/api.php` - Separated routes by role
2. `routes/web.php` - Separated routes by role
3. `app/Http/Controllers/API/ActivityApiController.php` - Updated to filter by role

---

## Summary

All routes have been properly separated according to `full_role_based_feature_access.md`:

1. ✅ **Farm Creation**: Farmer-only routes
2. ✅ **Alert Creation**: Admin-only routes
3. ✅ **Activity Creation**: Farmer-only routes
4. ✅ **Farm Points**: Farmer-only routes
5. ✅ **User Management**: Admin-only routes
6. ✅ **System Management**: Admin-only routes
7. ✅ **Shared Routes**: Both roles can access (filtered by ownership)

All data is properly filtered based on role and ownership at both the route level (middleware) and controller level (policies).

