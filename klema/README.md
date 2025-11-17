# 🌱 KLEMA – Climate-Smart Agriculture Monitoring System

**Version:** 1.2.0

KLEMA is a comprehensive climate-smart agriculture platform designed to help farmers, cooperatives, and agricultural agencies adapt to the challenges of climate change. By combining real-time weather monitoring, interactive farm mapping, intelligent activity recommendations, and automated alert systems, KLEMA empowers users to make informed farming decisions and reduce risks from extreme weather events.

## 🌍 Introduction

Climate change impacts agriculture by altering weather patterns, reducing yields, and increasing risks from droughts, floods, and pests. KLEMA provides reliable weather insights, farm visualization, historical data analysis, and automated recommendations to strengthen decision-making and food security.

## 🎯 Objectives

- Integrate real-time weather API data with historical climate analysis
- Provide interactive farm mapping with OpenStreetMap integration
- Support secure user authentication with role-based access control
- Deliver responsive dashboards accessible on desktop, tablet, and mobile devices
- Enable automated weather-based activity recommendations and alerts
- Facilitate data export and reporting for analysis

## ⚙️ Core Features

### 1. Weather Monitoring
- **Real-time Weather Data**: Temperature, humidity, rainfall, wind speed, and atmospheric pressure
- **Weather Forecasts**: Multi-day forecasts (up to 16 days) for planning
- **Historical Weather Data**: Store and analyze historical weather patterns (up to 30 days)
- **Location-based Weather**: Support for both coordinate-based and location name queries
- **Weather History API**: Access historical weather data by date
- **Database-First Caching**: Intelligent caching system that checks database before making API calls, reducing external API usage
- **Client-Side Caching**: In-memory caching on frontend for instant data retrieval on subsequent requests
- **Optimized Loading**: Multi-layer caching ensures fast view loading with minimal API calls

### 2. Farm Management
- **Interactive Farm Mapping**: Visualize farm plots and boundaries using Leaflet maps with OpenStreetMap tiles
- **Farm Points of Interest**: Mark, save, and manage specific points within farms
- **Farm Boundaries**: Define and store farm boundary coordinates
- **Farm Details**: Store comprehensive farm information including location, size, and metadata
- **Multi-farm Support**: Users can manage multiple farms

### 3. Activity Management & Recommendations
- **Activity Scheduling**: Plan and track farming activities with date-based scheduling
- **Weather-based Recommendations**: Intelligent activity advisor that suggests optimal timing based on weather forecasts
- **Activity Types**: Support for various farming activities (planting, harvesting, irrigation, etc.)
- **Suitability Checking**: Pre-activity weather suitability validation
- **Activity Calendar**: Visual calendar interface for managing activities
- **Status Tracking**: Track activity status (pending, in_progress, completed, cancelled)

### 4. Alert System
- **Automated Weather Alerts**: System automatically generates alerts based on forecast conditions
- **Severe Weather Warnings**: Alerts for extreme weather conditions (heavy rain, drought, high winds, etc.)
- **Farm-specific Alerts**: Alerts tied to specific farm locations
- **Alert Resolution**: Mark alerts as resolved when addressed
- **Forecast Warnings**: Proactive warnings based on upcoming weather conditions
- **Alert Automation**: Background service scans farms and creates alerts automatically

### 5. Data Export & Reporting
- **Weather Data Export**: Export weather data to CSV/PDF formats
- **Farm Data Export**: Export farm information and associated data
- **Activity Data Export**: Export activity records with timestamps
- **Export History**: Track and download previous exports
- **Multiple Export Formats**: Support for CSV and PDF exports

### 6. User Management & Authentication
- **Secure Authentication**: Laravel Sanctum-based API authentication with Bearer tokens
- **Email Verification**: Required email verification for account activation
- **Password Reset**: Secure password reset functionality
- **Role-based Access**: Support for different user roles (farmer, admin)
- **Session Management**: Web-based session authentication for dashboard access
- **Token Management**: Personal access tokens for API authentication

### 7. Dashboard & Analytics
- **Responsive Dashboard**: Mobile-first design accessible on all devices
- **Weather Dashboard**: Real-time weather display with charts and visualizations
- **Calendar View**: Interactive calendar for activity and weather planning
- **Settings Management**: User preferences and system configuration
- **User Management Interface**: Admin interface for managing users (accessible to all farmers)

## 🛠 Tech Stack

### Frontend
- **Framework**: Vue.js 3.5+ (Composition API)
- **Build Tool**: Vite 7.0+
- **UI Framework**: Bootstrap 5.2+ with Tailwind CSS 4.0
- **Mapping**: Leaflet 1.9+ with OpenStreetMap tiles
- **3D Visualization**: Cesium 1.135+ (optional)
- **HTTP Client**: Axios 1.12+
- **Styling**: SASS/SCSS

### Backend
- **Framework**: Laravel 12.0+ (PHP 8.2+)
- **API**: RESTful API architecture
- **Authentication**: Laravel Sanctum 4.2+ (Bearer token authentication)
- **Database**: PostgreSQL (primary), MySQL support available
- **PDF Generation**: DomPDF 3.1+
- **Queue System**: Laravel Queue for background jobs

### Mobile & Desktop
- **Mobile Framework**: Capacitor 6.0+ (Android & iOS support)
- **Desktop Framework**: Electron 31.0+ (Windows, macOS, Linux)
- **Build Tools**: Electron Builder for desktop packaging

### External Services
- **Weather API**: OpenWeatherMap API (real-time, forecast, and historical data)
- **Mapping**: OpenStreetMap tiles for map rendering

### Development Tools
- **Package Manager**: Composer (PHP), npm (Node.js)
- **Code Quality**: Laravel Pint
- **Testing**: PHPUnit 11.5+
- **Logging**: Laravel Pail

## 🏗 System Architecture

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

### Request Flow

1. **Frontend** (Vue.js/Electron/Capacitor) checks client-side cache first (instant if cached)
2. If not cached, makes API request with Bearer token
3. **Backend** (Laravel) validates authentication via Sanctum middleware
4. **Weather Service** checks database cache before external API:
   - Current weather: Uses database if data is < 30 minutes old
   - Forecasts: Uses database if all requested days are available and not expired
   - Historical data: Uses database storage
5. Only fetches from OpenWeatherMap API if database cache is stale or missing
6. Automatically stores fetched data to database for future requests
7. **Response** returns JSON data to frontend, which caches it client-side
8. Subsequent requests use cached data until expiration

## 📁 Project Structure

```
klema/
├── app/
│   ├── Console/Commands/        # Artisan commands
│   ├── Http/
│   │   ├── Controllers/         # Web controllers
│   │   │   └── API/            # API controllers
│   │   └── Middleware/         # Custom middleware
│   ├── Models/                 # Eloquent models
│   ├── Policies/               # Authorization policies
│   ├── Providers/              # Service providers
│   └── Services/               # Business logic services
│       ├── ActivityAdvisor.php
│       ├── AlertAutomationService.php
│       └── WeatherService.php
├── bootstrap/                  # Application bootstrap
├── config/                     # Configuration files
├── database/
│   ├── migrations/             # Database migrations
│   └── seeders/               # Database seeders
├── public/                     # Public web root
├── resources/
│   ├── js/                     # Frontend JavaScript
│   │   ├── components/         # Vue components
│   │   │   └── Views/         # Main view components
│   │   ├── composables/       # Vue composables
│   │   └── services/          # Frontend services
│   │       ├── auth.js        # Authentication service
│   │       └── http.js        # HTTP client service
│   ├── css/                    # Stylesheets
│   └── views/                  # Blade templates
├── routes/
│   ├── api.php                # API routes
│   └── web.php                  # Web routes
├── storage/                    # Storage directory
├── tests/                      # Test files
├── android/                    # Capacitor Android project
├── electron/                   # Electron main process files
├── capacitor.config.ts         # Capacitor configuration
├── vite.config.js             # Vite configuration
├── composer.json               # PHP dependencies
└── package.json                # Node.js dependencies
```

## 🚀 Getting Started

### Prerequisites

- **PHP**: 8.2 or higher
- **Node.js**: 20.x or higher
- **Composer**: Latest version
- **PostgreSQL**: 12+ (or MySQL 8+)
- **OpenWeatherMap API Key**: Get one from [openweathermap.org](https://openweathermap.org/api)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd sia-project-1/klema
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Update `.env` file** with your configuration:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=klema
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   WEATHER_API_KEY=your_openweathermap_api_key
   APP_URL=http://localhost:8000
   ```

6. **Run database migrations**
   ```bash
   php artisan migrate
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

### Development

**Start development servers:**
```bash
composer dev
```

This command runs:
- Laravel development server (port 8000)
- Queue worker
- Laravel Pail (log viewer)
- Vite dev server (hot module replacement)

**Or run individually:**
```bash
# Backend
php artisan serve

# Frontend (in separate terminal)
npm run dev

# Queue worker (in separate terminal)
php artisan queue:listen
```

### Building for Production

**Web:**
```bash
npm run build
```

**Desktop (Electron):**
```bash
# Linux
npm run electron:build:linux

# Windows
npm run electron:build:win

# macOS
npm run electron:build:mac
```

**Mobile (Capacitor):**
```bash
# Build web assets
npm run build

# Sync with Capacitor
npm run cap:sync

# Open Android Studio
npm run cap:open:android

# Open Xcode (macOS only)
npm run cap:open:ios
```

## 📡 API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login and get Bearer token
- `POST /api/auth/logout` - Logout and revoke token
- `GET /api/auth/me` - Get current user info
- `POST /api/auth/password/forgot` - Request password reset
- `POST /api/auth/password/reset` - Reset password
- `POST /api/auth/token` - Issue token for session-authenticated users

### Weather
- `GET /api/weather/current` - Get current weather
- `GET /api/weather/forecast` - Get weather forecast
- `GET /api/weather/history` - Get historical weather data
- `GET /api/weather/historical/{date}` - Get weather for specific date

### Farms
- `GET /api/farms` - List user's farms
- `POST /api/farms` - Create new farm
- `GET /api/farms/{farm}` - Get farm details
- `PUT /api/farms/{farm}` - Update farm
- `DELETE /api/farms/{farm}` - Delete farm
- `POST /api/farms/{farm}/points` - Add point to farm
- `GET /api/farms/{farm}/weather` - Get weather for farm
- `GET /api/map/farms` - Get map data for all farms

### Alerts
- `GET /api/alerts` - List alerts
- `POST /api/alerts` - Create alert
- `GET /api/alerts/active` - Get active alerts
- `GET /api/alerts/forecast-warnings` - Get forecast warnings
- `PATCH /api/alerts/{alert}/resolve` - Resolve alert

### Activities
- `GET /api/activities` - List activities
- `POST /api/activities` - Create activity
- `GET /api/activities/meta` - Get activity metadata
- `GET /api/activities/recommendation` - Get activity recommendations
- `GET /api/activities/{activity}` - Get activity details
- `PUT /api/activities/{activity}` - Update activity
- `DELETE /api/activities/{activity}` - Delete activity

### Exports
- `GET /api/exports` - List exports
- `POST /api/exports/weather` - Export weather data
- `POST /api/exports/farms` - Export farm data
- `POST /api/exports/activities` - Export activity data
- `GET /api/exports/{export}/download` - Download export file

### Admin
- `GET /api/admin/stats` - Get system statistics
- `GET /api/admin/farmers` - Get farmers with farms
- `POST /api/admin/weather/update` - Manually update weather data
- `GET /api/admin/users` - List users
- `POST /api/admin/users/{user}/reset-password` - Reset user password

**Note:** All API endpoints (except auth endpoints) require `Authorization: Bearer {token}` header.

## 🔐 Authentication

KLEMA uses **Laravel Sanctum** for API authentication:

1. **Web Authentication**: Session-based authentication for dashboard access
2. **API Authentication**: Bearer token authentication for API requests
3. **Token Issuance**: Tokens can be obtained via:
   - Login endpoint (`/api/auth/login`)
   - Token endpoint (`/api/auth/token`) for session-authenticated users

**Token Format:**
```
Authorization: Bearer {sanctum_token}
```

**Token Expiration:** Configurable via `SANCTUM_EXPIRATION` in `.env` (default: 120 minutes)

## 🗄️ Database Schema

### Application Tables (KLEMA-specific)
- `users` - User accounts with email verification and role-based access
- `farms` - Farm information with boundaries, coordinates, and metadata
- `farm_points` - Points of interest within farms
- `activities` - Farming activities with scheduling and status tracking
- `alerts` - Weather and system alerts with automation support
- `weather_data` - Historical weather data storage (current weather cached for 30 minutes)
- `forecasts` - Forecast data storage with expiration tracking (forecasts expire after date passes)
- `exports` - Export job tracking and file management

### Laravel Framework Tables

#### Authentication & Sessions
- `password_reset_tokens` - Password reset token storage (created by Laravel)
- `sessions` - Web session data storage (when using database session driver)
- `personal_access_tokens` - Laravel Sanctum API tokens for authentication

#### Caching (Database Cache Driver)
- `cache` - General cache storage (when `CACHE_STORE=database`)
- `cache_locks` - Cache lock storage for preventing race conditions

#### Queue System (Database Queue Driver)
- `jobs` - Queued job storage (when `QUEUE_CONNECTION=database`)
- `job_batches` - Batch job tracking for grouped queue operations
- `failed_jobs` - Failed queue job storage with exception details

#### System Tables
- `migrations` - Laravel migration tracking (automatically managed)

**Note:** Framework tables are automatically created by Laravel's default migrations. They're essential for core Laravel functionality:
- Cache tables are used when `CACHE_STORE=database` in `.env`
- Queue tables are used when `QUEUE_CONNECTION=database` in `.env`
- Session table is used when `SESSION_DRIVER=database` in `.env`

## 🔄 Background Services

### Alert Automation Service
Automatically scans farms and creates alerts based on weather forecasts:
- Runs via scheduled command or queue job
- Checks forecast conditions for each farm
- Creates alerts for severe weather conditions
- Prevents duplicate alerts within time window

### Activity Advisor Service
Provides intelligent recommendations for farming activities:
- Analyzes weather forecasts for activity dates
- Suggests optimal timing based on weather conditions
- Provides warnings for unfavorable conditions

## ⚡ Performance Optimizations

### Weather Data Caching Strategy

KLEMA implements a multi-layer caching system to optimize weather data loading and reduce API calls:

#### 1. Database-First Caching (Backend)
The `WeatherService` checks the database before making external API calls:

- **Current Weather**: 
  - Checks database for data less than 30 minutes old
  - Falls back to API only if database cache is stale
  - Automatically stores API responses to database

- **Forecasts**:
  - Checks database for all requested forecast days
  - Uses database data if all days are available and not expired
  - Forecasts expire 2 hours after the forecast date ends
  - Automatically stores forecast data to `forecasts` table

- **Historical Data**:
  - Primarily sourced from database storage
  - Fetches from external API only for missing dates
  - Historical data stored permanently for analysis

**Benefits:**
- Significant reduction in external API calls
- Faster response times (database queries are much faster than API calls)
- Lower API usage costs
- Improved reliability (works even if external API is temporarily unavailable)

#### 2. Client-Side Caching (Frontend)
The `useWeatherAPI` composable implements in-memory caching:

- **Cache Durations**:
  - Current weather: 30 minutes
  - Forecasts: 1 hour
  - Historical data: 2 hours

- **Cache Management**:
  - Automatic expiration based on data age
  - Cache size limited to 50 entries (LRU-style eviction)
  - Transparent to components (no code changes needed)

**Benefits:**
- Instant loading when switching between views
- Reduced server load
- Better user experience with faster interactions
- Works across all views (Dashboard, Calendar, Weather Map, etc.)

#### 3. Cache Layers Flow

```
User Request
    ↓
┌─────────────────┐
│ Client Cache    │ ← Check first (instant if hit)
│ (In-Memory)     │
└────────┬────────┘
         │ (miss)
         ↓
┌─────────────────┐
│  API Request    │
└────────┬────────┘
         ↓
┌─────────────────┐
│ Database Cache  │ ← Check before external API
│ (PostgreSQL)    │
└────────┬────────┘
         │ (miss)
         ↓
┌─────────────────┐
│ External API    │ ← Only if needed
│ (OpenWeatherMap)│
└─────────────────┘
```

#### 4. Database Schema for Caching

**`weather_data` table:**
- Stores current weather snapshots
- Indexed by location, coordinates, and date
- Used for current weather (30 min freshness) and historical queries

**`forecasts` table:**
- Stores forecast data with expiration dates
- Indexed by location, coordinates, and forecast date
- Each forecast expires 2 hours after its date passes
- Supports queries by location name or coordinates

#### 5. Optimization Impact

**Before Optimization:**
- Every view load → API call
- Multiple views → Multiple redundant API calls
- Slow loading times
- High API usage

**After Optimization:**
- First load → API call (stored to database)
- Subsequent loads → Database (fast)
- View switches → Client cache (instant)
- ~90% reduction in API calls for typical usage

#### 6. Best Practices

- **Automatic**: No manual cache management needed - system handles everything automatically
- **Transparent**: All existing code continues to work without changes
- **Reliable**: Falls back gracefully if cache is unavailable
- **Fresh**: Data automatically refreshes when cache expires

## 🧪 Testing

Run tests:
```bash
composer test
```

Or with PHPUnit directly:
```bash
php artisan test
```

## 📦 Deployment

### Docker Deployment
The project includes a Dockerfile for containerized deployment:
```bash
docker build -t klema .
docker run -p 8000:8000 klema
```

### Railway Deployment
When deploying to Railway, ensure you configure the following:

#### Email Configuration
**Important:** Railway blocks direct SMTP connections to external services like Gmail. You must use a cloud email service.

**Note:** All mailer configurations coexist in `config/mail.php` - changing `MAIL_MAILER` only selects which one to use. Your settings remain in the config file.

**Recommended Email Services:**
1. **SendGrid** (Recommended - Uses Web API, no domain verification required for basic usage)
   ```env
   MAIL_MAILER=sendgrid
   SENDGRID_API_KEY=SG.your_sendgrid_api_key  # Your SendGrid API key (starts with SG.)
   MAIL_FROM_ADDRESS=noreply@yourdomain.com  # Any email address
   MAIL_FROM_NAME="KLEMA"
   ```
   **Note:** This uses SendGrid's Web API (not SMTP). Get your API key from https://app.sendgrid.com/settings/api_keys

2. **Resend** (Requires domain verification)
   ```env
   MAIL_MAILER=resend
   RESEND_KEY=your_resend_api_key
   MAIL_FROM_ADDRESS=noreply@yourdomain.com
   MAIL_FROM_NAME="KLEMA"
   ```

3. **Postmark**
   ```env
   MAIL_MAILER=postmark
   POSTMARK_TOKEN=your_postmark_token
   MAIL_FROM_ADDRESS=noreply@yourdomain.com
   MAIL_FROM_NAME="KLEMA"
   ```

4. **AWS SES**
   ```env
   MAIL_MAILER=ses
   AWS_ACCESS_KEY_ID=your_access_key
   AWS_SECRET_ACCESS_KEY=your_secret_key
   AWS_DEFAULT_REGION=us-east-1
   MAIL_FROM_ADDRESS=noreply@yourdomain.com
   MAIL_FROM_NAME="KLEMA"
   ```

#### Queue Worker Configuration
Email sending is queued to prevent blocking HTTP requests. Ensure you run a queue worker:

1. **Add a Railway service** for the queue worker
2. **Set the command** to: `php artisan queue:work --tries=3`
3. **Or use Railway's built-in process manager** if available

#### Required Environment Variables
```env
# Queue (required for email sending)
QUEUE_CONNECTION=database

# Email timeout (prevents long waits on failures)
MAIL_TIMEOUT=5

# Application
APP_URL=https://your-app.railway.app
APP_ENV=production
APP_DEBUG=false
```

### Production Checklist
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Build frontend assets: `npm run build`
- [ ] Set up queue worker: `php artisan queue:work`
- [ ] Configure scheduled tasks (cron)
- [ ] Set up SSL/TLS certificates
- [ ] Configure database backups

### Scheduled Tasks
Add to crontab for production:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🌐 Platform Support

### Web
- Modern browsers (Chrome, Firefox, Safari, Edge)
- Responsive design for mobile, tablet, and desktop
- Progressive Web App (PWA) capabilities

### Desktop
- **Windows**: NSIS installer and portable version
- **macOS**: DMG and ZIP packages
- **Linux**: AppImage and DEB packages

### Mobile
- **Android**: APK builds via Capacitor
- **iOS**: Native app via Capacitor (requires macOS for building)

## 📝 Configuration

### Key Configuration Files
- `.env` - Environment variables
- `config/sanctum.php` - Sanctum authentication settings
- `config/activities.php` - Activity type definitions
- `config/farm.php` - Farm-related settings
- `config/mail.php` - Mail configuration (SendGrid, SMTP, etc.)
- `config/services.php` - Third-party service credentials (SendGrid, OpenWeather, etc.)
- `capacitor.config.ts` - Capacitor mobile configuration
- `vite.config.js` - Frontend build configuration

### Weather Data Caching Configuration

Caching behavior is built-in and optimized by default. Cache durations:

**Backend (Database):**
- Current weather: 30 minutes freshness
- Forecasts: Until date passes + 2 hours
- Historical: Permanent storage

**Frontend (Client-side):**
- Current weather: 30 minutes
- Forecasts: 1 hour
- Historical: 2 hours

Cache durations are optimized for balance between freshness and performance. Adjust in code if needed:
- Backend: `app/Services/WeatherService.php` (check `getStoredCurrentWeatherBy*` methods)
- Frontend: `resources/js/composables/useWeatherAPI.js` (check `CACHE_DURATION` constant)

### Environment Variables
```env
# Application
APP_NAME=KLEMA
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=klema
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Weather API
WEATHER_API_KEY=your_api_key_here

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SANCTUM_EXPIRATION=120
SANCTUM_TOKEN_PREFIX=klema_

# Queue (required for email queuing)
QUEUE_CONNECTION=database

# Email Configuration - SendGrid Web API (Recommended for Railway)
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=SG.your_sendgrid_api_key  # Your SendGrid API key (starts with SG.)
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="KLEMA"

# Alternative: Resend (requires domain verification)
# MAIL_MAILER=resend
# RESEND_KEY=your_resend_api_key

# Alternative: Postmark
# MAIL_MAILER=postmark
# POSTMARK_TOKEN=your_postmark_token
```

## 👥 Target Users

- **Local farmers & smallholder growers** - Primary users for weather monitoring and activity planning
- **Agricultural cooperatives** - Group management and coordination
- **Extension officers** - Support and advisory services
- **LGUs & agricultural agencies** - Regional monitoring and data collection
- **NGOs & community organizations** - Community-based agricultural support

## ✅ Expected Outcomes

- Farmers gain reliable, map-based dashboards for decision-making
- Centralized, secure storage of farm & weather data
- Automated alerts & recommendations to improve resilience against climate risks
- Accessible across desktop, tablet, and mobile devices
- Data export capabilities for analysis and reporting
- Intelligent activity recommendations based on weather conditions

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

---

**Version 1.2.0** - Last updated: 2025

### Changelog

#### Version 1.2.0 (2025)
- ✨ **Major Performance Improvement**: Implemented database-first weather data caching
- ✨ Added `forecasts` table for efficient forecast data storage
- ✨ Client-side caching for instant view loading
- ✨ Multi-layer caching system (Client → Database → API)
- ⚡ Significantly reduced external API calls (~90% reduction)
- ⚡ Faster view loading times
- 🔧 Automatic cache expiration and management
- 📚 Comprehensive caching documentation

#### Version 1.1.0 (2025)
- Initial stable release
- Core features implemented
