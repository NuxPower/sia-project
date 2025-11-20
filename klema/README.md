# 🌱 KLEMA – Climate-Smart Agriculture Monitoring System

**Version:** 1.2.0

KLEMA is a comprehensive climate-smart agriculture platform designed to help farmers, cooperatives, and agricultural agencies adapt to the challenges of climate change. By combining real-time weather monitoring, interactive farm mapping, intelligent activity recommendations, and automated alert systems, KLEMA empowers users to make informed farming decisions and reduce risks from extreme weather events.

## CHAPTER III TECHNICAL BACKGROUND

### Technology Stack

#### i. Frontend Layer

**Vue.js Framework**

KLEMA utilizes Vue.js 3.5+ with the Composition API as its primary frontend framework. The application is built using a component-based architecture that enables reactive data binding and efficient state management. Vue.js provides a flexible and performant foundation for building interactive user interfaces.

**Mapping Libraries**

The system integrates Leaflet 1.9+ for interactive map rendering and visualization. Leaflet is a lightweight, open-source JavaScript library that provides seamless integration with OpenStreetMap tiles for displaying farm locations, boundaries, and weather data overlays. The mapping functionality enables users to visualize farm plots, mark points of interest, and draw farm boundaries directly on the map interface.

Additional frontend technologies include:
- **Vite 7.0+**: Modern build tool for fast development and optimized production builds
- **Bootstrap 5.2+ with Tailwind CSS 4.0**: UI framework for responsive design and styling
- **Axios 1.12+**: HTTP client for API communication
- **SASS/SCSS**: Preprocessor for advanced styling capabilities

#### ii. Backend and Application Logic

**Railway**

The KLEMA backend is deployed on Railway, a cloud platform that provides automated deployment, scaling, and infrastructure management. Railway handles the hosting of the Laravel application, database connections, and queue workers, ensuring reliable and scalable service delivery.

**Laravel Framework**

The backend is built on Laravel 12.0+ (PHP 8.2+), a modern PHP framework that provides a robust foundation for RESTful API development. Laravel's architecture includes:

- **RESTful API Architecture**: Clean, standardized API endpoints for all system operations
- **Eloquent ORM**: Object-relational mapping for database interactions
- **Queue System**: Laravel Queue for background job processing (email sending, weather data storage)
- **PDF Generation**: DomPDF 3.1+ for generating export documents
- **Service Layer**: Business logic encapsulated in service classes for maintainability

#### iii. Database Management and Storage

**PostgreSQL Database**

KLEMA uses PostgreSQL as its primary database management system. PostgreSQL provides robust data integrity, advanced indexing capabilities, and excellent performance for complex queries. The database stores:

- **User Data**: User accounts, authentication tokens, and session information
- **Farm Data**: Farm information, boundaries, coordinates, and points of interest
- **Activity Records**: Farming activities with scheduling and status tracking
- **Alert Data**: Weather alerts and system notifications
- **Weather Data**: Historical weather data and cached current weather information
- **Forecast Data**: Weather forecast information with expiration tracking
- **Export Records**: Export job tracking and file management

The system implements a database-first caching strategy where weather data is stored in PostgreSQL before making external API calls, significantly reducing API usage and improving response times.

#### iv. Weather API

**OpenWeatherMap**

KLEMA integrates with the OpenWeatherMap API to retrieve real-time weather data, forecasts, and historical weather information. The WeatherService class manages all interactions with the OpenWeatherMap API, including:

- **Current Weather**: Real-time temperature, humidity, rainfall, wind speed, and atmospheric pressure
- **Weather Forecasts**: Multi-day forecasts (up to 16 days) for planning agricultural activities
- **Historical Weather Data**: Access to past weather conditions for analysis and trend identification
- **Location-based Queries**: Support for both coordinate-based and location name queries

The system implements intelligent caching mechanisms that check the database before making API calls, reducing external API usage by approximately 90% while maintaining data freshness.

#### v. Mapping

**OpenStreetMap (OSM)**

KLEMA utilizes OpenStreetMap tiles for map rendering and visualization. OpenStreetMap provides free, open-source map data that enables the system to display geographic information without licensing restrictions. The integration includes:

- **Base Map Layers**: Standard OSM tiles for geographic context
- **Weather Overlay Layers**: Integration with OpenWeatherMap tile services for weather visualization (clouds, precipitation, temperature, wind, pressure)
- **Interactive Features**: Map click events, boundary drawing, and point marking capabilities
- **Coordinate System**: Support for latitude/longitude coordinate-based operations

#### vi. Authentication

**Sanctum Authentication**

KLEMA implements Laravel Sanctum 4.2+ for secure API authentication. Sanctum provides a lightweight authentication system that supports:

- **Bearer Token Authentication**: API requests authenticated using Bearer tokens in the Authorization header
- **Session Authentication**: Web-based session authentication for dashboard access
- **Token Management**: Personal access tokens with configurable expiration (default: 120 minutes)
- **Email Verification**: Required email verification for account activation
- **Password Reset**: Secure password reset functionality with token-based verification
- **Role-based Access Control**: Support for different user roles (farmer, admin) with policy-based authorization

## CHAPTER IV SYSTEM DESIGN AND IMPLEMENTATION

### System Architecture

The KLEMA system follows a three-tier architecture pattern:

```
┌─────────────────────────────────────────────────────────────┐
│                    Client Applications                      │
├──────────────┬──────────────┬──────────────┬───────────────┤
│   Web App    │  Desktop App │  Android App │   iOS App     │
│  (Vue.js)    │  (Electron)  │ (Capacitor)  │ (Capacitor)   │
└──────┬───────┴──────┬───────┴──────┬───────┴───────┬────────┘
       │              │              │              │
       └──────────────┴──────────────┴──────────────┘
                          │
                          ▼
       ┌──────────────────────────────────────┐
       │      Laravel REST API (Backend)      │
       │  - Authentication (Sanctum)          │
       │  - Weather API Integration            │
       │  - Farm Management                    │
       │  - Activity Advisor                   │
       │  - Alert Automation                   │
       └──────────────┬───────────────────────┘
                      │
        ┌─────────────┴─────────────┐
        │                           │
        ▼                           ▼
┌───────────────┐         ┌──────────────────┐
│  PostgreSQL   │         │  OpenWeatherMap  │
│   Database    │         │      API         │
│               │         │                  │
│ - Users       │         │ - Current Weather│
│ - Farms       │         │ - Forecasts      │
│ - Activities  │         │ - History        │
│ - Alerts      │         │                  │
│ - Weather Data│         │                  │
│ - Forecasts   │         │                  │
│ - Exports     │         │                  │
└───────────────┘         └──────────────────┘
```

### System Architecture Diagrams

**Figure 1. System Architecture Flow**

```
┌─────────────────────────────────────┐
│   User Interface                    │
│   (Browser / Frontend UI - Vue.js)  │
└──────────────┬──────────────────────┘
               │
               │ HTTP Requests
               ▼
┌─────────────────────────────────────┐
│   Laravel Controllers               │
│   (Farm, Alert, Weather, Activity)  │
└──────────────┬──────────────────────┘
               │
               │ Calls Models/Services
               ▼
┌─────────────────────────────────────┐
│   Service Layer (Business Logic)    │
│   - WeatherService                  │
│   - ActivityAdvisor                 │
│   - AlertAutomationService          │
└──────────────┬──────────────────────┘
               │              │
               │              │ Calls External API
               │              ▼
               │    ┌──────────────────────────┐
               │    │  External API            │
               │    │  (OpenWeatherMap)        │
               │    │  via WeatherService.php  │
               │    └──────────────────────────┘
               │
               │ Calls Models
               ▼
┌─────────────────────────────────────┐
│   Models (Eloquent ORM)             │
│   (User, Farm, Activity, Alert, etc.)│
└──────────────┬──────────────────────┘
               │
               │ SQL Queries
               ▼
┌─────────────────────────────────────┐
│   Database (PostgreSQL)             │
│   (Users, Farms, Weather, etc.)     │
└─────────────────────────────────────┘
```

**Figure 2. Detailed System Flowchart**

```
┌─────────────────────────────────────┐
│   Client (Browser / Mobile)         │
└──────────────┬──────────────────────┘
               │
               │ HTTP Requests
               ▼
┌─────────────────────────────────────┐
│   API / Web Route                   │
│   (routes/api.php, routes/web.php)  │
│   Receives and maps incoming        │
│   HTTP requests                     │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   Authentication & Middleware Check │
│   (Sanctum, Verified, etc.)         │
└──────┬──────────────┬───────────────┘
       │              │              │
       │              │              │
       │ Unauthenticated    Authenticated   Role-based Policy
       │              │              │
       ▼              ▼              ▼
┌──────────┐  ┌─────────────┐  ┌──────────────┐
│   Auth   │  │   Main      │  │   Policy     │
│Controllers│  │ Controllers │  │   Layer      │
│          │  │             │  │              │
│- Login   │  │- FarmApi    │  │- FarmPolicy  │
│- Register│  │- WeatherApi │  │- ExportPolicy│
│- Reset   │  │- AlertApi   │  │- Activity    │
│          │  │- ActivityApi│  │  Policy      │
│          │  │- ExportApi  │  │- UserPolicy  │
│          │  │- Dashboard  │  │              │
└──────────┘  └──────┬──────┘  └──────┬───────┘
                     │                │
                     └────────┬───────┘
                              │
                              ▼
           ┌─────────────────────────────────────┐
           │   Service Layer (Business Logic)    │
           │                                     │
           │   WeatherService.php                │
           │   → connects to external APIs       │
           │                                     │
           │   ActivityAdvisor                   │
           │   → handles activity recommendations│
           │                                     │
           │   AlertAutomationService            │
           │   → handles automated alerts        │
           │                                     │
           │   Export Services                   │
           │   → handles CSV/XLS/PDF generation  │
           └──────────────┬──────────────────────┘
                          │
                          │ Calls Models
                          ▼
         ┌─────────────────────────────────────┐
         │   Eloquent Models                   │
         │                                     │
         │   - User                            │
         │   - Farm                            │
         │   - FarmPoint                       │
         │   - WeatherData                     │
         │   - Forecast                        │
         │   - Activity                        │
         │   - Alert                           │
         │   - Export                          │
         │   - UserSettings                    │
         └──────────────┬──────────────────────┘
                        │
                        │ SQL Queries
                        ▼
         ┌─────────────────────────────────────┐
         │   Database (PostgreSQL)             │
         │                                     │
         │   Tables:                           │
         │   - users                           │
         │   - farms                           │
         │   - farm_points                     │
         │   - weather_data                    │
         │   - forecasts                       │
         │   - activities                      │
         │   - alerts                          │
         │   - exports                         │
         │   - user_settings                   │
         │                                     │
         │   Managed through migrations        │
         │   and accessed via Eloquent         │
         └──────────────┬──────────────────────┘
                        │
                        ▼
         ┌─────────────────────────────────────┐
         │   External Systems & Integrations   │
         │                                     │
         │   Weather API (OpenWeatherMap)      │
         │   → WeatherService                  │
         │                                     │
         │   File Storage                      │
         │   → Export CSV/XLS/PDF              │
         │   → ExportController                │
         │                                     │
         │   Provides external data,           │
         │   reports, and backups              │
         └─────────────────────────────────────┘
```

**Figure 3. Entity-Relationship Diagram (ERD)**

```
┌─────────────────────────────────────┐
│           users                     │
├─────────────────────────────────────┤
│ PK  id                  INT         │
│     name                VARCHAR     │
│     email               VARCHAR(100)│
│     password            VARCHAR(255)│
│     role                ENUM        │
│     email_verified_at   TIMESTAMP   │
│     created_at          TIMESTAMP   │
│     updated_at          TIMESTAMP   │
└──────────────┬──────────────────────┘
               │
               │ 1
               │
               │ hasMany
               │
       ┌───────┴───────┬──────────────┬──────────────┬──────────────┐
       │               │              │              │              │
       │ N             │ N            │ N            │ N            │ 1
       │               │              │              │              │
       ▼               ▼              ▼              ▼              ▼
┌──────────┐  ┌──────────────┐ ┌──────────┐          ┌──────────────┐
│  farms   │  │ activities   │ │ exports  │          │user_settings │
├──────────┤  ├──────────────┤ ├──────────┤          ├──────────────┤
│PK farm_id│  │PK id         │ │PK export_│          │PK id         │
│FK user_id│  │FK user_id    │ │    id    │          │FK user_id    │
│farm_name │  │activity_type │ │file_name │          │...           │
│latitude  │  │field         │ │file_path │          │              │
│longitude │  │start_date    │ │created_at│          │              │
│...       │  │end_date      │ │updated_at│          │              │
│created_at│  │status        │ │          │          │              │
│updated_at│  │...           │ │          │          │              │
└────┬─────┘  └──────────────┘ └──────────┘          └──────────────┘
     │
     │ 1
     │
     │ hasMany
     │
     ├──────────┬──────────┬──────────┬──────────┐
     │          │          │          │          │
     │ N        │ N        │ N        │ N        │
     │          │          │          │          │
     ▼          ▼          ▼          ▼          ▼
┌──────────┐┌──────────┐┌──────────┐┌──────────┐┌──────────┐
│farm_     ││weather_  ││forecasts ││ alerts   ││          │
│points    ││data      ││          ││          ││          │
├──────────┤├──────────┤├──────────┤├──────────┤│          │
│PK point_ ││PK weather││PK        ││PK alert_ ││          │
│    id    ││    _id   ││forecast_ ││    id    ││          │
│FK farm_id││FK farm_id││    id    ││FK farm_id││          │
│label     ││temperature││FK farm_ ││alert_type││          │
│latitude  ││humidity  ││    id    ││message   ││          │
│longitude ││rainfall  ││location_ ││issued_at ││          │
│point_type││wind_speed││  name    ││resolved  ││          │
│created_at││condition ││latitude  ││...       ││          │
│updated_at││recorded_ ││longitude ││          ││          │
│          ││  at      ││forecast_ ││          ││          │
│          ││          ││  date    ││          ││          │
│          ││          ││temp_max  ││          ││          │
│          ││          ││temp_min  ││          ││          │
│          ││          ││condition ││          ││          │
│          ││          ││expires_at││          ││          │
│          ││          ││...       ││          ││          │
└──────────┘└──────────┘└──────────┘└──────────┘└──────────┘

Relationships:
- users 1:N farms (one user can have many farms)
- users 1:N activities (one user can have many activities)
- users 1:N exports (one user can have many exports)
- users 1:1 user_settings (one user has one settings record)
- farms 1:N farm_points (one farm can have many points)
- farms 1:N weather_data (one farm can have many weather records)
- farms 1:N forecasts (one farm can have many forecasts)
- farms 1:N alerts (one farm can have many alerts)
```

**User Interface (Browser / Frontend UI)**

The frontend layer is built with Vue.js and provides a responsive, interactive user interface accessible via web browsers, desktop applications (Electron), and mobile applications (Capacitor). The UI components include:

- **Weather Dashboard**: Real-time weather display with charts and visualizations
- **Farm Management Interface**: Interactive maps for farm visualization and management
- **Activity Calendar**: Visual calendar interface for managing farming activities
- **Alert Management**: Interface for viewing and resolving weather alerts
- **Settings Management**: User preferences and system configuration
- **Export Interface**: Tools for exporting data in CSV and PDF formats

**Laravel Controllers (Farm, Alert, Weather)**

The backend implements a controller-based architecture with dedicated API controllers:

- **FarmApiController**: Handles farm CRUD operations, farm point management, and map data retrieval
- **WeatherApiController**: Manages weather data retrieval (current, forecast, historical)
- **AlertApiController**: Processes alert creation, retrieval, and resolution
- **ActivityApiController**: Manages farming activity operations and recommendations
- **AuthApiController**: Handles user authentication, registration, and password management
- **ExportApiController**: Manages data export operations
- **SettingsApiController**: Handles user settings management

**Models (Eloquent ORM for DB Operations)**

The system uses Eloquent ORM models for database interactions:

- **User**: User accounts with relationships to farms, activities, and exports
- **Farm**: Farm information with boundaries, coordinates, and relationships to points and alerts
- **FarmPoint**: Points of interest within farms
- **Activity**: Farming activities with scheduling and status tracking
- **Alert**: Weather and system alerts with resolution tracking
- **WeatherData**: Historical weather data storage
- **Forecast**: Forecast data with expiration tracking
- **Export**: Export job tracking and file management
- **UserSettings**: User preferences and configuration

**Database (PostgreSQL)**

The PostgreSQL database serves as the primary data storage layer, implementing:

- **Relational Data Structure**: Normalized database schema with foreign key relationships
- **Indexing Strategy**: Optimized indexes on frequently queried columns (location, coordinates, dates)
- **Caching Layer**: Database-first caching for weather data to reduce external API calls
- **Transaction Management**: ACID compliance for data integrity

**External APIs (Weather API via WeatherService.php)**

The WeatherService class manages all external API interactions:

- **API Request Management**: Handles HTTP requests to OpenWeatherMap API
- **Error Handling**: Robust error handling and fallback mechanisms
- **Data Transformation**: Converts API responses to standardized internal formats
- **Caching Logic**: Implements database-first caching before making API calls
- **Background Jobs**: Queues weather data storage jobs for non-blocking operations

### Backend Module

**Controllers (Logic and Request Handlers)**

The backend module implements RESTful API controllers that handle HTTP requests and responses:

- **Request Validation**: Input validation using Laravel's validation rules
- **Authorization**: Policy-based authorization checks for resource access
- **Response Formatting**: Standardized JSON response format
- **Error Handling**: Consistent error response structure

**Models (Database Interaction Layer)**

Eloquent models provide an object-oriented interface to the database:

- **Relationships**: Defines relationships between entities (User hasMany Farms, Farm hasMany Activities, etc.)
- **Query Scopes**: Reusable query constraints for common operations
- **Accessors/Mutators**: Data transformation on model attributes
- **Events**: Model events for automatic actions (e.g., creating related records)

**Services (External Integration Layer)**

Service classes encapsulate business logic and external integrations:

- **WeatherService**: Manages OpenWeatherMap API interactions, caching, and data storage
- **ActivityAdvisor**: Provides intelligent recommendations for farming activities based on weather forecasts
- **AlertAutomationService**: Automatically scans farms and creates alerts based on weather conditions

**Policies and Middleware (Security and Access Control)**

The system implements security through policies and middleware:

- **Policies**: 
  - ActivityPolicy: Controls access to activity resources
  - ExportPolicy: Manages export permissions
  - FarmPolicy: Enforces farm ownership and access rules
  - UserPolicy: Handles user management permissions

- **Middleware**:
  - EnsureUserHasRole: Validates user roles for admin operations
  - Sanctum Authentication: Validates Bearer tokens for API requests
  - Email Verification: Ensures users have verified their email addresses

### Database Layer

The database layer implements a comprehensive schema for storing all system data:

**Application Tables:**
- `users`: User accounts with email verification and role-based access
- `farms`: Farm information with boundaries, coordinates, and metadata
- `farm_points`: Points of interest within farms
- `activities`: Farming activities with scheduling and status tracking
- `alerts`: Weather and system alerts with automation support
- `weather_data`: Historical weather data storage (current weather cached for 30 minutes)
- `forecasts`: Forecast data storage with expiration tracking
- `exports`: Export job tracking and file management
- `user_settings`: User preferences and configuration

**Laravel Framework Tables:**
- `password_reset_tokens`: Password reset token storage
- `sessions`: Web session data storage
- `personal_access_tokens`: Laravel Sanctum API tokens for authentication
- `cache` / `cache_locks`: Cache storage (when using database cache driver)
- `jobs` / `job_batches` / `failed_jobs`: Queue system tables (when using database queue driver)
- `migrations`: Migration tracking

### Map And Weather Integration

**FarmController.php and FarmPoint Model**

The FarmApiController manages farm-related operations including map integration:

- **Farm CRUD Operations**: Create, read, update, and delete farm records
- **Boundary Management**: Store and retrieve farm boundary coordinates as GeoJSON
- **Point Management**: Add, retrieve, and manage points of interest within farms via the FarmPoint model
- **Map Data Endpoint**: Provides aggregated farm data for map visualization (`/api/map/farms`)
- **Weather Integration**: Retrieves weather data for specific farm locations

**Data Handling**

The system implements efficient data handling mechanisms:

- **GeoJSON Format**: Farm boundaries stored as GeoJSON for compatibility with mapping libraries
- **Coordinate Validation**: Ensures latitude/longitude values are within valid ranges
- **Spatial Queries**: Database queries optimized for geographic data retrieval
- **Data Transformation**: Converts between database format and API response format

**Weather Integration**

Weather data integration is handled through multiple layers:

- **WeatherService**: Central service for all weather-related operations
- **Database Caching**: Stores weather data in PostgreSQL before making external API calls
- **Client-Side Caching**: In-memory caching on frontend for instant data retrieval
- **Multi-layer Caching**: Client cache → Database cache → External API (only if needed)
- **Background Jobs**: Non-blocking storage of weather data to database
- **Forecast Management**: Automatic expiration of forecast data after date passes

### API Endpoints

**Authentication & User Management**

- `POST /api/auth/register` - Register new user account
- `POST /api/auth/login` - Login and receive Bearer token
- `POST /api/auth/logout` - Logout and revoke token
- `GET /api/auth/me` - Get current authenticated user information
- `POST /api/auth/password/forgot` - Request password reset link
- `POST /api/auth/password/reset` - Reset password with token
- `POST /api/auth/token` - Issue token for session-authenticated users
- `POST /api/auth/email/resend` - Resend email verification
- `POST /api/auth/email/verification-notification` - Send verification email

**Farm Management**

- `GET /api/farms` - List all farms for authenticated user
- `POST /api/farms` - Create new farm
- `GET /api/farms/{farm}` - Get farm details
- `PUT /api/farms/{farm}` - Update farm information
- `DELETE /api/farms/{farm}` - Delete farm
- `POST /api/farms/{farm}/points` - Add point of interest to farm
- `GET /api/farms/{farm}/weather` - Get weather data for farm location
- `GET /api/map/farms` - Get map data for all user farms
**Farm Map Integration**

The farm map integration endpoints provide:

- **Map Data Aggregation**: `/api/map/farms` returns all farms with coordinates and boundaries for map rendering
- **Farm Weather**: `/api/farms/{farm}/weather` retrieves weather data specific to farm location
- **Point Management**: Farm points can be added and managed through the farm endpoints

**Weather Integration**

- `GET /api/weather/current` - Get current weather data (by location or coordinates)
- `GET /api/weather/forecast` - Get weather forecast (1-16 days)
- `GET /api/weather/history` - Get historical weather data
- `GET /api/weather/historical/{date}` - Get weather for specific date

All weather endpoints support:
- Location name queries (e.g., "Manila, Philippines")
- Coordinate-based queries (latitude/longitude)
- Database-first caching (checks database before API call)
- Automatic data storage to database for future requests

**Activity and Alert Management**

**Activities:**
- `GET /api/activities` - List all activities for authenticated user
- `POST /api/activities` - Create new activity
- `GET /api/activities/{activity}` - Get activity details
- `PUT /api/activities/{activity}` - Update activity
- `DELETE /api/activities/{activity}` - Delete activity
- `GET /api/activities/meta` - Get activity metadata (types, statuses)
- `GET /api/activities/recommendation` - Get weather-based activity recommendations

**Alerts:**
- `GET /api/alerts` - List all alerts
- `POST /api/alerts` - Create new alert
- `GET /api/alerts/active` - Get active (unresolved) alerts
- `GET /api/alerts/forecast-warnings` - Get forecast-based warnings
- `PATCH /api/alerts/{alert}/resolve` - Mark alert as resolved

**Data Export and Reports**

- `GET /api/exports` - List export history
- `POST /api/exports/weather` - Export weather data (CSV/PDF)
- `POST /api/exports/farms` - Export farm data (CSV/PDF)
- `POST /api/exports/activities` - Export activity data (CSV/PDF)
- `GET /api/exports/{export}/download` - Download export file

**Dashboard & Analytics**

- `GET /api/admin/stats` - Get system statistics (admin/farmer dashboard)
- `GET /api/admin/farmers` - Get farmers with farms (admin dashboard)
- `POST /api/admin/weather/update` - Manually trigger weather data update
- `GET /api/admin/users` - List all users (admin)
- `POST /api/admin/users/{user}/reset-password` - Reset user password (admin)
- `GET /api/admin/users/{user}/sessions` - Get user sessions (admin)
- `DELETE /api/admin/users/{user}/sessions` - Revoke user sessions (admin)
- `GET /api/settings` - Get user settings
- `PUT /api/settings` - Update user settings
- `POST /api/settings/reset` - Reset user settings to defaults

**Note:** All API endpoints (except authentication endpoints) require `Authorization: Bearer {token}` header for authentication.

---

**Version 1.2.0** - Last updated: 2025

## 👩‍💻 Contributors

- Ayuban, John Gabrielle A.
- Callanta, Yohan Lukin D.
- Galvarole, Sharlene Mae M.
- Lagrosas, Cydiemar O.
- Merto, Dara P.
- Panaque, Renelle Janos A.
- Polinar, Lean Emmaus L.

## 📄 License

This project is licensed under the MIT License.

## 🔗 Links

- **Homepage**: https://klema.up.railway.app/app
- **Author Email**: nahoynikulcallanta@gmail.com
