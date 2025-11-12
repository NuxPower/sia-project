# System Architecture - Centralized Administration Model

## Overview
This document confirms the system architecture: **A single-instance system where one or more Admins oversee ALL data, and each Farmer strictly oversees their own registered farms**.

---

## Architecture Model

### 🏛️ Centralized Administration Structure

**Single-Instance System**: The entire system operates as one centralized instance where:
- **One or more Admins** oversee ALL farmers and ALL data across the system
- **Each Farmer** strictly oversees only their own registered farm(s)
- **Central Database**: All data is stored in a single database with shared `users` and `farms` tables

### 🧑‍💻 Administrator Role

**Primary Objective**: Data Management, System Oversight, and Reporting

**Access Level**: **ALL DATA** across **ALL FARMERS**

#### What Admins Can Do:
1. ✅ **View ALL Farms** - See every farm registered by every farmer
2. ✅ **View ALL Alerts** - See alerts for all farms across all farmers
3. ✅ **View ALL Activities** - See activities from all farmers
4. ✅ **Create Alerts** - Create alerts manually for any farm
5. ✅ **Update/Delete Any Farm** - Manage any farm in the system
6. ✅ **Update/Delete Any Alert** - Manage any alert in the system
7. ✅ **Update/Delete Any Activity** - Manage any activity in the system
8. ✅ **Export ALL Data** - Export all farms, weather data, and activities
9. ✅ **Manage Users** - Create, update, delete user accounts
10. ✅ **System Statistics** - View system-wide statistics and analytics
11. ✅ **Manual Weather Updates** - Trigger weather updates from external API

#### What Admins CANNOT Do:
- ❌ **Create Farms** - Farmers only can create farms
- ❌ **Create Activities** - Farmers only can create activities
- ❌ **Add Farm Points** - Farmers only can add farm points

### 🧑‍🌾 Farmer Role

**Primary Objective**: Information, Recommendations, and Farm-Level Data Entry

**Access Level**: **OWN DATA ONLY** (strictly their registered farms)

#### What Farmers Can Do:
1. ✅ **Create Farms** - Register their own farms
2. ✅ **View Own Farms** - See only their registered farms
3. ✅ **Update Own Farms** - Modify only their farms
4. ✅ **Delete Own Farms** - Delete only their farms
5. ✅ **Add Farm Points** - Add points to their own farms
6. ✅ **View Own Alerts** - See alerts for their farms only
7. ✅ **Resolve Own Alerts** - Resolve alerts for their farms
8. ✅ **Delete Own Alerts** - Delete alerts for their farms
9. ✅ **Create Activities** - Record their own farm activities
10. ✅ **View Own Activities** - See only their activities
11. ✅ **Update Own Activities** - Modify only their activities
12. ✅ **Delete Own Activities** - Delete only their activities
13. ✅ **Export Own Data** - Export their own farms, weather, and activities
14. ✅ **View Weather** - See weather data for their farms
15. ✅ **View Map** - See their farms on the map

#### What Farmers CANNOT Do:
- ❌ **Create Alerts** - Only admins can create alerts
- ❌ **View Other Farmers' Farms** - Strictly isolated to own farms
- ❌ **View Other Farmers' Alerts** - Strictly isolated to own farms
- ❌ **View Other Farmers' Activities** - Strictly isolated to own activities
- ❌ **Access Admin Endpoints** - Cannot access system management
- ❌ **Manage Users** - Cannot manage user accounts
- ❌ **View System Statistics** - Cannot view system-wide statistics

---

## Database Structure

### Central Tables

#### `users` Table
- **All Users**: Contains both admins and farmers in a single table
- **Role Field**: `enum('admin', 'farmer')` to distinguish roles
- **Central Management**: Admins manage all users from this table

#### `farms` Table
- **All Farms**: Contains farms from all farmers in a single table
- **Foreign Key**: `user_id` links to `users` table
- **Ownership**: Each farm belongs to one farmer (via `user_id`)
- **Admin Access**: Admins can view/manage all farms, farmers see only their own

#### `alerts` Table
- **All Alerts**: Contains alerts for all farms in a single table
- **Foreign Key**: `farm_id` links to `farms` table
- **Filtering**: Farmers see alerts for their farms only, admins see all alerts

#### `activities` Table
- **All Activities**: Contains activities from all farmers in a single table
- **Foreign Key**: `user_id` links to `users` table
- **Filtering**: Farmers see their own activities only, admins see all activities

#### `weather_data` Table
- **All Weather Data**: Contains weather data for all farms
- **Foreign Key**: `farm_id` links to `farms` table (nullable)
- **Filtering**: Weather data is filtered by farm ownership

#### `exports` Table
- **All Exports**: Contains exports from all users
- **Foreign Key**: `user_id` links to `users` table
- **Filtering**: Users see their own exports, admins see all exports

---

## Data Filtering Implementation

### Controller-Level Filtering

#### FarmController / FarmApiController
```php
// Admins see ALL farms, farmers see only their farms
if (!$user->isAdmin()) {
    $farmsQuery->where('user_id', $user->id);
}
```

#### AlertController / AlertApiController
```php
// Admins see ALL alerts, farmers see only their farm alerts
if (!$user->isAdmin()) {
    $alertsQuery->whereHas('farm', function($query) use ($user) {
        $query->where('user_id', $user->id);
    });
}
```

#### ActivityController / ActivityApiController
```php
// Admins see ALL activities, farmers see only their activities
if (!$user->isAdmin()) {
    $activitiesQuery->where('user_id', $user->id);
}
```

#### ExportController
```php
// Admins export ALL data, farmers export only their data
if (!$user->isAdmin()) {
    // Filter by user_id or farm.user_id
}
```

#### DashboardController
```php
// Admins see ALL farms, alerts, activities + system statistics
// Farmers see only their farms, alerts, activities
if (!$user->isAdmin()) {
    // Filter by user_id
} else {
    // Show system statistics
    $systemStats = $this->getSystemStatistics();
}
```

### Policy-Level Protection

#### FarmPolicy
```php
public function view(User $user, Farm $farm): bool
{
    // Admins can view ANY farm, farmers can view only their own
    return $user->id === $farm->user_id || $user->isAdmin();
}

public function create(User $user): bool
{
    // Only farmers can create farms
    return $user->isFarmer();
}
```

#### AlertPolicy
```php
public function view(User $user, Alert $alert): bool
{
    // Admins can view ANY alert, farmers can view only their farm's alerts
    if ($user->isAdmin()) {
        return true;
    }
    return $alert->farm->user_id === $user->id;
}

public function create(User $user): bool
{
    // Only admins can create alerts
    return $user->isAdmin();
}
```

#### ActivityPolicy
```php
public function view(User $user, Activity $activity): bool
{
    // Admins can view ANY activity, farmers can view only their own
    return $this->ownsActivity($user, $activity) || $user->isAdmin();
}

public function create(User $user): bool
{
    // Only farmers can create activities
    return $user->isFarmer();
}
```

---

## Route Structure

### Shared Routes (Both Roles)
- **Middleware**: `auth:sanctum, verified, role:admin,farmer`
- **Data Filtering**: Applied at controller level based on role
- **Access**: Both roles can access, but see different data

### Farmer-Only Routes
- **Middleware**: `auth:sanctum, verified, role:farmer`
- **Routes**: Farm creation, Activity creation, Farm points
- **Access**: Only farmers can access

### Admin-Only Routes
- **Middleware**: `auth:sanctum, verified, role:admin`
- **Routes**: Alert creation, System management, User management
- **Access**: Only admins can access

---

## Key Features

### 1. Centralized Data Management
- ✅ All data in single database
- ✅ Admins have "all-seeing" view of entire system
- ✅ Farmers have isolated view of their own data
- ✅ No data fragmentation or multi-tenant isolation

### 2. Role-Based Access Control
- ✅ Middleware protection at route level
- ✅ Policy protection at controller level
- ✅ Data filtering at query level
- ✅ Multi-layer security

### 3. Data Isolation for Farmers
- ✅ Farmers cannot see other farmers' data
- ✅ Farmers cannot access admin features
- ✅ Strict ownership checks prevent data leakage
- ✅ Policy layer enforces isolation

### 4. Administrative Oversight
- ✅ Admins can view all data
- ✅ Admins can manage all resources
- ✅ Admins can export all data
- ✅ Admins can create alerts for any farm
- ✅ Admins have system-wide statistics

---

## Implementation Verification

### ✅ Admin Access to All Data
- [x] `FarmApiController::index()` - Admins see all farms (no filter)
- [x] `AlertApiController::index()` - Admins see all alerts (no filter)
- [x] `ActivityApiController::index()` - Admins see all activities (no filter)
- [x] `DashboardController::index()` - Admins see all farms, alerts, activities
- [x] `ExportController` - Admins export all data (no filter)
- [x] `FarmApiController::mapData()` - Admins see all farms on map

### ✅ Farmer Access to Own Data Only
- [x] `FarmApiController::index()` - Farmers see only their farms (`where('user_id', $user->id)`)
- [x] `AlertApiController::index()` - Farmers see only their farm alerts (`whereHas('farm', ...)`)
- [x] `ActivityApiController::index()` - Farmers see only their activities (`where('user_id', $user->id)`)
- [x] `DashboardController::index()` - Farmers see only their farms, alerts, activities
- [x] `ExportController` - Farmers export only their data
- [x] `FarmApiController::mapData()` - Farmers see only their farms on map

### ✅ Route Separation
- [x] Farm creation restricted to farmers only
- [x] Alert creation restricted to admins only
- [x] Activity creation restricted to farmers only
- [x] User management restricted to admins only
- [x] System management restricted to admins only

### ✅ Policy Protection
- [x] `FarmPolicy::create()` - Only farmers can create farms
- [x] `AlertPolicy::create()` - Only admins can create alerts
- [x] `ActivityPolicy::create()` - Only farmers can create activities
- [x] All policies check ownership or admin status

---

## Summary

The system implements a **centralized administration model** where:

1. **Single Database Instance**: All data stored in one database
2. **Admin Oversight**: One or more admins oversee ALL farmers and ALL data
3. **Farmer Isolation**: Each farmer strictly oversees only their own registered farms
4. **Data Filtering**: Controllers filter data based on role (admins see all, farmers see own)
5. **Route Separation**: Creation routes are role-specific (farmers create farms/activities, admins create alerts)
6. **Policy Protection**: Policies enforce ownership checks (admins bypass, farmers restricted)
7. **Multi-Layer Security**: Middleware, policies, and controller-level filtering ensure proper access control

This architecture supports **one or more Admins overseeing the entire system and all Farmer data**, with each **Farmer strictly overseeing only their own registered farm(s)**.

