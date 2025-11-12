# Farmer-Farm Relationship & Admin Access

## Overview

This document explains how the system tracks which farmer is registered to which farm, and how admins (including multiple admins) can access this information.

---

## Database Structure

### Relationship Model
- **Farms Table**: Contains `user_id` foreign key that links to the `users` table
- **Relationship**: 
  - `User` (farmer) `hasMany` `Farm`
  - `Farm` `belongsTo` `User` (farmer/owner)

### Key Points
1. **One-to-Many**: Each farmer can own multiple farms
2. **Ownership**: Each farm belongs to exactly one farmer (via `user_id`)
3. **Admins**: Admins don't own farms directly, but can view/manage all farms

---

## How It Works

### For Farmers
- When a farmer creates a farm, the `user_id` is automatically set to their ID
- Farmers can only see and manage their own farms
- API responses include their own farm data only

### For Admins
- Admins can see **all farms** from **all farmers**
- All API responses now include the `user` (farmer/owner) relationship
- Admins can identify which farmer owns which farm through the included user data

---

## API Endpoints

### 1. Get All Farms (with Owner Information)
**Endpoint**: `GET /api/farms`
**Access**: Both admins and farmers

**Response includes**:
```json
{
  "success": true,
  "farms": [
    {
      "farm_id": 1,
      "farm_name": "Green Valley Farm",
      "user_id": 5,
      "user": {
        "id": 5,
        "name": "John Farmer",
        "email": "john@example.com",
        "role": "farmer"
      },
      "latitude": 8.9475,
      "longitude": 125.5406,
      ...
    }
  ]
}
```

**For Admins**: See all farms with owner information
**For Farmers**: See only their own farms

---

### 2. Get All Farmers with Their Farms (Admin Only)
**Endpoint**: `GET /api/admin/farmers`
**Access**: Admin only

**Purpose**: Provides a comprehensive view of all farmers and their associated farms. This is especially useful when there are multiple admins who need to see the farmer-farm relationships.

**Response**:
```json
{
  "success": true,
  "total_farmers": 3,
  "farmers": [
    {
      "user_id": 5,
      "name": "John Farmer",
      "email": "john@example.com",
      "email_verified_at": "2025-01-15T10:00:00.000000Z",
      "farms_count": 2,
      "farms": [
        {
          "farm_id": 1,
          "farm_name": "Green Valley Farm",
          "latitude": 8.9475,
          "longitude": 125.5406,
          "size_hectares": 10.5,
          "soil_type": "clay",
          "active_alerts_count": 2,
          "created_at": "2025-01-10T08:00:00.000000Z"
        },
        {
          "farm_id": 2,
          "farm_name": "Sunset Fields",
          ...
        }
      ]
    },
    {
      "user_id": 6,
      "name": "Jane Grower",
      "email": "jane@example.com",
      "farms_count": 1,
      "farms": [...]
    }
  ]
}
```

**Use Cases**:
- View all farmers and their farm registrations
- Identify which farmer owns which farm
- See farm statistics per farmer
- Track active alerts per farmer's farms

---

### 3. Get Farm Details (with Owner)
**Endpoint**: `GET /api/farms/{farm_id}`
**Access**: Admin (any farm) or Farmer (own farms only)

**Response includes**:
```json
{
  "success": true,
  "farm": {
    "farm_id": 1,
    "farm_name": "Green Valley Farm",
    "user_id": 5,
    "user": {
      "id": 5,
      "name": "John Farmer",
      "email": "john@example.com",
      "role": "farmer"
    },
    "weatherData": [...],
    "farmPoints": [...],
    "alerts": [...]
  }
}
```

---

### 4. Get Alerts (with Farm and Owner)
**Endpoint**: `GET /api/alerts`
**Access**: Both admins and farmers

**Response includes**:
```json
{
  "success": true,
  "alerts": [
    {
      "alert_id": 1,
      "alert_type": "weather",
      "message": "Heavy rain expected",
      "farm_id": 1,
      "farm": {
        "farm_id": 1,
        "farm_name": "Green Valley Farm",
        "user_id": 5,
        "user": {
          "id": 5,
          "name": "John Farmer",
          "email": "john@example.com"
        }
      }
    }
  ]
}
```

**For Admins**: See all alerts with farm and owner information
**For Farmers**: See only alerts for their own farms

---

### 5. Map Data (with Owner Information)
**Endpoint**: `GET /api/map/farms`
**Access**: Both admins and farmers

**Response includes**:
```json
{
  "success": true,
  "farm_features": {
    "type": "FeatureCollection",
    "features": [
      {
        "type": "Feature",
        "geometry": {...},
        "properties": {
          "farm_id": 1,
          "farm_name": "Green Valley Farm",
          "owner_id": 5,
          "owner_name": "John Farmer",
          "owner_email": "john@example.com",
          ...
        }
      }
    ]
  }
}
```

---

## Multiple Admins Support

### Current Implementation
- **All admins** can see all farms and all farmers
- **No admin-specific farm assignments** - all admins have equal access
- **Shared view** - any admin can view/manage any farm

### Admin Capabilities
1. ✅ View all farms with owner information
2. ✅ View all farmers and their farms (`/api/admin/farmers`)
3. ✅ Create alerts for any farm
4. ✅ View all alerts with farmer information
5. ✅ Export all farm data
6. ✅ View system statistics

### If You Need Admin-Specific Assignments
If you need to track which admin manages which farms (for accountability or workload distribution), you would need to:

1. Create a pivot table: `admin_farm_assignments` or `farm_managers`
2. Add relationship: `Farm belongsToMany Admin` (through pivot)
3. Add middleware/policy to check admin assignments
4. Filter farms by assigned admin

**Current system**: All admins share full access (simpler model)

---

## Summary

### What's Tracked
- ✅ Which farmer owns which farm (`farms.user_id` → `users.id`)
- ✅ All farms include owner information in API responses
- ✅ Admins can see all farmer-farm relationships

### What's Available
- ✅ API endpoint to list all farmers with their farms (`/api/admin/farmers`)
- ✅ All farm endpoints include owner (`user`) information
- ✅ All alert endpoints include farm and owner information
- ✅ Map data includes owner information in properties

### For Multiple Admins
- ✅ All admins can access all farms
- ✅ All admins can see which farmer owns which farm
- ✅ No admin-specific restrictions (shared access model)

---

## Example Usage

### Admin wants to see all farmers and their farms:
```bash
GET /api/admin/farmers
Authorization: Bearer {admin_token}
```

### Admin wants to see a specific farm with owner info:
```bash
GET /api/farms/1
Authorization: Bearer {admin_token}
# Response includes farm.user with owner details
```

### Admin wants to see all alerts with farmer information:
```bash
GET /api/alerts
Authorization: Bearer {admin_token}
# Each alert includes alert.farm.user with owner details
```

---

## Notes

- The relationship is **one-way**: Farms → Farmer (via `user_id`)
- Admins don't have a direct relationship to farms
- All admins have equal access to all farms
- Farmer information is automatically included in all relevant API responses

