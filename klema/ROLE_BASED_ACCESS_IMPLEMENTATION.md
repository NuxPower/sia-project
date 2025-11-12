# Role-Based Access Control Implementation

## Overview
This document outlines the role-based access control (RBAC) implementation based on `full_role_based_feature_access.md`.

## Role Separation

### [ROLE 1: ADMINISTRATOR]
**Primary Focus**: Data Management, System Oversight, and Reporting

#### 1. SYSTEM REPORTING & EXPORT ✅
- ✅ Export all farm data (PDF, CSV, XLSX)
- ✅ Export all weather data
- ✅ Export all recorded farm activities
- ✅ Utilize local storage or S3 integration
- **Access**: Admin-only endpoints protected with `role:admin` middleware

#### 2. SYSTEM CONTROL & ANALYTICS ✅
- ✅ Aggregate key system data for admin dashboard
- ✅ View system-wide statistics (total farms, users, alerts, activities)
- ✅ Manually trigger weather updates from external API
- **Access**: Admin-only endpoints protected with `role:admin` middleware

#### 3. USER & ACCESS MANAGEMENT ✅
- ✅ Manage user accounts (CRUD operations)
- ✅ Reset user passwords
- ✅ Manage user sessions
- ✅ Control access permissions via Policy Layer
- ✅ Track and log user actions for auditing
- **Access**: Admin-only routes protected with `role:admin` middleware

#### 4. ALERT MANAGEMENT ✅
- ✅ Create alerts manually for any farm
- ✅ View all alerts (across all farms)
- ✅ Manage all alerts (update, resolve, delete)
- **Access**: Admin can create alerts (AlertPolicy::create() returns true for admin)
- **Note**: Farmers can only VIEW alerts for their farms, cannot create

#### 5. FARM MANAGEMENT ❌
- ❌ **Admins CANNOT create farms** (FarmPolicy::create() restricted to farmers only)
- ✅ Admins can VIEW all farms
- ✅ Admins can UPDATE any farm
- ✅ Admins can DELETE any farm
- **Access**: Admins can manage farms but not create them

---

### [ROLE 2: FARMER]
**Primary Focus**: Information, Recommendations, and Farm-Level Data Entry

#### 1. FARM MANAGEMENT (CRUD OPERATIONS) ✅
- ✅ Create farms (FarmPolicy::create() allows farmers only)
- ✅ Read/view own farms (FarmPolicy::view() checks ownership)
- ✅ Update own farms (FarmPolicy::update() checks ownership)
- ✅ Delete own farms (FarmPolicy::delete() checks ownership)
- ✅ Register farm points (latitude/longitude coordinates)
- ✅ Manage farm information and details
- **Access**: Farmers can only manage their own farms

#### 2. WEATHER AND MAPPING INSIGHTS ✅
- ✅ Access map-based dashboard for farm visualization
- ✅ View own farm locations on interactive map
- ✅ View real-time weather data for own farms
- ✅ Retrieve current weather and forecasts for own farms
- ✅ View weather information and alerts for own farms
- ✅ Receive timely alerts and recommendations
- ✅ Input or update records through forms
- **Access**: Weather data filtered by farm ownership
- **Map Data**: Farmers see only their farms (FarmApiController::mapData() filters by user_id)

#### 3. ACTIVITY MANAGEMENT ✅
- ✅ Record new farm activities (ActivityPolicy::create() allows farmers)
- ✅ View own activities (ActivityPolicy::view() checks ownership)
- ✅ Update own activities (ActivityPolicy::update() checks ownership)
- ✅ Delete own activities (ActivityPolicy::delete() checks ownership)
- ✅ Retrieve current weather or farm alerts for own farms
- **Access**: Farmers can only manage their own activities

#### 4. EXPORT FUNCTIONALITY ✅
- ✅ Export own farm data (CSV, PDF)
- ✅ Export own weather data (CSV, PDF)
- ✅ Export own activity data (CSV, PDF)
- ✅ View own export history
- **Access**: ExportController filters data by user ownership for farmers

#### 5. ALERT MANAGEMENT ❌
- ❌ **Farmers CANNOT create alerts** (AlertPolicy::create() returns false for farmers)
- ✅ Farmers can VIEW alerts for their farms only
- ✅ Farmers can RESOLVE alerts for their farms
- ✅ Farmers can DELETE alerts for their farms
- **Access**: AlertPolicy restricts creation to admin only

---

### [SHARED FEATURES] ✅
- ✅ Login, Register, Logout
- ✅ Retrieving authenticated user profile
- ✅ Viewing map-based information (filtered by role)
- ✅ Viewing alert information (filtered by role)
- ✅ Retrieving past weather data stored in database (filtered by role)

**Note**: While these are "shared" features, data is filtered based on role:
- **Farmers**: See only their own data
- **Admins**: See all data

---

## Policy Implementations

### FarmPolicy
```php
- viewAny(): Admin or Farmer can view farms list
- view(): User owns farm OR is admin
- create(): ✅ RESTRICTED TO FARMERS ONLY
- update(): User owns farm OR is admin
- delete(): User owns farm OR is admin
```

### AlertPolicy
```php
- viewAny(): Admin or Farmer can view alerts
- view(): User owns farm OR is admin
- create(): ✅ RESTRICTED TO ADMIN ONLY
- update(): User owns farm OR is admin
- delete(): User owns farm OR is admin
- resolve(): User owns farm OR is admin
```

### ActivityPolicy
```php
- viewAny(): Admin or Farmer can view activities
- view(): User owns activity OR is admin
- create(): ✅ FARMERS CAN CREATE
- update(): User owns activity OR is admin
- delete(): User owns activity OR is admin
```

### ExportPolicy
```php
- viewAny(): Both admin and farmer can view exports
- view(): User owns export OR is admin
- create(): Both admin and farmer can create exports
- download(): User owns export OR is admin
```

### UserPolicy
```php
- viewAny(): ✅ ADMIN ONLY
- view(): User is admin OR viewing own profile
- create(): ✅ ADMIN ONLY
- update(): User is admin OR updating own profile
- delete(): ✅ ADMIN ONLY (cannot delete self)
- resetPassword(): ✅ ADMIN ONLY
- manageSessions(): ✅ ADMIN ONLY
```

---

## Controller Filtering

### FarmController / FarmApiController
- **Farmers**: Filter by `user_id = auth()->id()`
- **Admins**: No filter (see all farms)

### AlertController / AlertApiController
- **Farmers**: Filter by `farm.user_id = auth()->id()`
- **Admins**: No filter (see all alerts)

### ActivityController / ActivityApiController
- **Farmers**: Filter by `user_id = auth()->id()`
- **Admins**: No filter (see all activities)

### ExportController
- **Farmers**: Export only their own data
  - Weather data: Filter by `farm.user_id = auth()->id()`
  - Farm data: Filter by `user_id = auth()->id()`
  - Activity data: Filter by `user_id = auth()->id()`
- **Admins**: Export all data (no filters)

### DashboardController
- **Farmers**: See only their farms, alerts, activities
- **Admins**: See all farms, alerts, activities, plus system statistics

### WeatherApiController
- **Farmers**: Weather data accessed through farm ownership (via FarmApiController::getWeatherData())
- **Admins**: Can access all weather data, plus manual weather updates

---

## Route Protection

### Admin-Only Routes
```php
Route::middleware('role:admin')->group(function () {
    // User management
    Route::resource('admin/users', UserController::class);
    Route::post('admin/users/{user}/reset-password', ...);
    Route::get('admin/users/{user}/sessions', ...);
    Route::delete('admin/users/{user}/sessions', ...);
    
    // System management
    Route::get('api/admin/stats', ...);
    Route::get('api/admin/farmers', ...);
    Route::post('api/admin/weather/update', ...);
});
```

### Shared Routes (Filtered by Role)
```php
Route::middleware(['auth:sanctum', 'verified', 'role:admin,farmer'])->group(function () {
    // Farm routes (farmers create, admins view/manage)
    Route::apiResource('farms', FarmApiController::class);
    
    // Alert routes (admins create, farmers view)
    Route::apiResource('alerts', AlertApiController::class);
    
    // Activity routes (farmers create, admins view all)
    Route::apiResource('activities', ActivityApiController::class);
    
    // Export routes (filtered by ownership)
    Route::prefix('exports')->group(...);
    
    // Weather routes (filtered by farm ownership)
    Route::prefix('weather')->group(...);
});
```

---

## Key Restrictions

### ✅ FARMERS CANNOT:
1. Create alerts (admin-only)
2. Create farms for other users (can only create for themselves)
3. View other users' farms
4. View other users' activities
5. View other users' alerts
6. Export other users' data
7. Manage user accounts
8. Access admin endpoints
9. View system statistics

### ✅ ADMINS CANNOT:
1. Create farms (farmer-only feature)
2. Delete their own admin account
3. Remove their own admin role

### ✅ BOTH ROLES CAN:
1. Login, register, logout
2. View their own profile
3. View map-based information (filtered by role)
4. View alert information (filtered by role)
5. Retrieve past weather data (filtered by role)

---

## Testing Checklist

### Admin Access Tests
- [ ] Admin can create users
- [ ] Admin can update users
- [ ] Admin can delete users (except self)
- [ ] Admin can reset user passwords
- [ ] Admin can manage user sessions
- [ ] Admin can create alerts for any farm
- [ ] Admin can view all alerts
- [ ] Admin can view all farms
- [ ] Admin can update any farm
- [ ] Admin can delete any farm
- [ ] Admin CANNOT create farms
- [ ] Admin can view all activities
- [ ] Admin can export all data
- [ ] Admin can access system statistics
- [ ] Admin can manually trigger weather updates

### Farmer Access Tests
- [ ] Farmer can create farms
- [ ] Farmer can view own farms
- [ ] Farmer can update own farms
- [ ] Farmer can delete own farms
- [ ] Farmer CANNOT create alerts
- [ ] Farmer can view alerts for own farms
- [ ] Farmer can resolve alerts for own farms
- [ ] Farmer can delete alerts for own farms
- [ ] Farmer can create activities
- [ ] Farmer can view own activities
- [ ] Farmer can update own activities
- [ ] Farmer can delete own activities
- [ ] Farmer can export own data
- [ ] Farmer can view weather for own farms
- [ ] Farmer can view map with own farms
- [ ] Farmer CANNOT view other users' data
- [ ] Farmer CANNOT access admin endpoints

---

## Files Modified

### Policies
- `app/Policies/FarmPolicy.php` - Restricted create() to farmers only
- `app/Policies/AlertPolicy.php` - Restricted create() to admin only
- `app/Policies/ActivityPolicy.php` - Allows farmers to create
- `app/Policies/ExportPolicy.php` - Allows both roles, filters by ownership
- `app/Policies/UserPolicy.php` - Admin-only user management

### Controllers
- All controllers filter data based on user role
- ExportController filters exports by user ownership
- DashboardController shows system stats for admin only
- FarmController/API filters farms by user_id for farmers
- AlertController/API filters alerts by farm.user_id for farmers
- ActivityController/API filters activities by user_id for farmers

---

## Summary

All role-based access controls have been implemented according to `full_role_based_feature_access.md`:

1. ✅ **Admins** can manage users, create alerts, view all data, export all data, and manage system
2. ✅ **Farmers** can create farms, manage their farms, create activities, view their alerts, and export their data
3. ✅ **Admins cannot create farms** (farmer-only feature)
4. ✅ **Farmers cannot create alerts** (admin-only feature)
5. ✅ All data is properly filtered based on role and ownership
6. ✅ All routes are protected with appropriate middleware and policies

