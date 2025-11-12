🌱 KLEMA – Climate-Smart Agriculture Monitoring System

KLEMA is a climate-smart agriculture platform designed to help farmers, cooperatives, and agricultural agencies adapt to the challenges of climate change. By combining real-time weather monitoring, interactive farm mapping, and data-driven recommendations, KLEMA empowers users to make informed farming decisions and reduce risks from extreme weather events.

## 🌍 Introduction

Climate change impacts agriculture by altering weather patterns, reducing yields, and increasing risks from droughts, floods, and pests. KLEMA provides reliable weather insights, farm visualization, and historical data analysis to strengthen decision-making and food security.

## 🎯 Objectives

- Integrate real-time weather API data with historical climate analysis
- Provide interactive farm mapping with OpenStreetMap
- Support secure user authentication and role-based dashboards
- Deliver responsive dashboards accessible on desktop, tablet, and mobile

## ⚙️ Features

### 1. Weather Monitoring
- Real-time weather data (temperature, humidity, rainfall, wind speed/direction)
- Historical weather data storage and trend analysis
- Weather forecasts (5-day and extended forecasts)
- Severe weather alerts and warnings
- Interactive weather map layers (clouds, precipitation, temperature, wind, pressure)
- Weather timeline visualization

### 2. Mapping Integration
- Interactive farm plots & boundaries visualized via Leaflet/OpenStreetMap
- Farm point management (mark/save/manage points of interest)
- Weather station locations
- GeoJSON boundary support for farm areas
- Click-to-query weather data at any location

### 3. User Management & Authentication
- **Laravel Sanctum** token-based authentication (Bearer tokens)
- Email verification required for account activation
- Role-based access control (Admin & Farmer roles)
- Secure API endpoints with token expiration and rotation
- Device/session management (view and revoke active tokens)
- Password complexity requirements (12+ chars, mixed case, numbers, symbols)
- Rate-limited login/registration attempts

### 4. Dashboard & Reporting
- Mobile-first responsive dashboard
- Weather dashboard with interactive map
- Calendar view for activity scheduling
- Alerts management interface
- Settings panel with device management
- Export weather/farm data (CSV with timestamps) - Admin only
- Activity scheduling with weather suitability checks

### 5. Farm Management
- Create, update, and delete farms
- Add farm points with labels and types
- View farm-specific weather data
- Farm boundary visualization (GeoJSON)
- Farm-to-user ownership restrictions

### 6. Alert System
- Automatic forecast-based weather warnings
- Manual alert creation (Admin only)
- Alert resolution tracking
- Active alerts display
- Alert-to-farm associations

### 7. Activity Management
- Schedule farming activities
- Weather suitability checks for activity dates
- Activity status tracking (pending, in_progress, completed, cancelled)
- Monthly activity calendar view

## 🛠 Tech Stack

### Frontend
- **Vue.js 3** - Progressive JavaScript framework
- **Leaflet** - Interactive map library
- **Three.js & Vanta.js** - 3D visualizations and effects
- **Axios** - HTTP client
- **Bootstrap 5** - UI framework
- **Tailwind CSS 4** - Utility-first CSS
- **Sass** - CSS preprocessor
- **Vite** - Build tool and dev server

### Backend
- **Laravel 12** - PHP framework
- **Laravel Sanctum** - API token authentication
- **PostgreSQL** - Primary database (SQLite/MySQL also supported)
- **Doctrine DBAL** - Database abstraction layer

### External Services
- **OpenWeatherMap API** - Real-time and historical weather data
- **OpenStreetMap** - Map tiles and geocoding

### Development Tools
- **PHPUnit** - Testing framework
- **Laravel Pint** - Code style fixer
- **Laravel Sail** - Docker development environment

## 📁 Project Structure

```
klema/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/          # API controllers (Auth, Farm, Weather, Alert, Activity)
│   │   │   └── ...           # Web controllers
│   │   └── Middleware/
│   │       └── EnsureUserHasRole.php
│   ├── Models/               # Eloquent models (User, Farm, Alert, Activity, WeatherData, etc.)
│   ├── Policies/             # Authorization policies
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       └── WeatherService.php
├── database/
│   ├── migrations/           # Database schema migrations
│   ├── seeders/              # Database seeders
│   └── factories/            # Model factories
├── resources/
│   ├── js/
│   │   ├── components/       # Vue components
│   │   │   ├── Views/        # Main view components (Dashboard, Calendar, Alerts, Settings)
│   │   │   └── ...           # UI components (WeatherMap, WeatherTimeline, etc.)
│   │   ├── composables/      # Vue composables (useWeatherAPI, useAlerts, etc.)
│   │   ├── services/         # API services (auth.js, http.js)
│   │   └── utils/            # Utility functions
│   ├── sass/                 # Stylesheets
│   └── views/                # Blade templates
├── routes/
│   ├── api.php               # API routes
│   └── web.php               # Web routes
└── tests/                    # PHPUnit tests
```

## 🔐 Authentication & Security

### Token-Based Authentication
KLEMA uses **Laravel Sanctum** for API authentication with Bearer tokens instead of JWT.

**Key Features:**
- Short-lived tokens (default: 60 minutes)
- "Remember me" tokens (default: 30 days / 43200 minutes)
- Automatic token rotation and refresh
- Device/session management
- Token abilities and scopes

### API Endpoints

**Authentication:**
- `POST /api/login` - Authenticate and receive token
- `POST /api/register` - Register new user
- `POST /api/logout` - Logout and revoke token
- `POST /api/token/refresh` - Rotate current token
- `GET /api/tokens` - List active tokens
- `DELETE /api/tokens/{id}` - Revoke specific token
- `GET /api/me` - Get authenticated user info

**Weather:**
- `GET /api/weather/current` - Current weather
- `GET /api/weather/forecast` - Weather forecast
- `GET /api/weather/history` - Historical weather
- `GET /api/weather/historical/{date}` - Specific date weather

**Farms:**
- `GET /api/farms` - List farms
- `POST /api/farms` - Create farm
- `GET /api/farms/{id}` - Get farm details
- `PUT /api/farms/{id}` - Update farm
- `DELETE /api/farms/{id}` - Delete farm
- `POST /api/farms/{id}/points` - Add farm point
- `GET /api/farms/{id}/weather` - Get farm weather data
- `GET /api/map/farms` - Get map data (GeoJSON)

**Alerts:**
- `GET /api/alerts` - List alerts
- `POST /api/alerts` - Create alert (Admin only)
- `GET /api/alerts/{id}` - Get alert
- `PATCH /api/alerts/{id}/resolve` - Resolve alert
- `GET /api/alerts/active` - Active alerts
- `GET /api/alerts/forecast-warnings` - Forecast warnings

**Activities:**
- `GET /api/activities` - List activities
- `POST /api/activities` - Create activity

### Security Features

1. **Password Requirements:**
   - Minimum 12 characters
   - Must include letters (mixed case), numbers, and symbols
   - Uncompromised password checking

2. **Rate Limiting:**
   - Login: 5 attempts per minute per email+IP, 30 per minute per IP
   - Registration: 3 attempts per minute per IP
   - Email verification: 6 attempts per hour

3. **HTTPS Enforcement:**
   - Automatic HTTPS in production (`APP_ENV=production`)
   - Trusted proxy support

4. **Role-Based Access Control:**
   - Admin: Full system access, can create alerts/exports
   - Farmer: Own farms/activities only, read-only for system data

5. **CSRF Protection:**
   - Web routes protected with Laravel CSRF
   - API routes stateless (no CSRF needed)

## 🏗 System Architecture

```
┌─────────────┐
│   Vue.js    │  Frontend (SPA)
│   Frontend  │  - Weather Dashboard
└──────┬──────┘  - Interactive Maps
       │         - Activity Calendar
       │ HTTP    - Alerts Management
       │ (Bearer │
       │ Tokens) │
┌──────▼──────┐
│   Laravel   │  Backend API
│   REST API  │  - Authentication (Sanctum)
└──────┬──────┘  - Weather Service
       │         - Farm Management
       │         - Authorization Policies
       │
       ├─────────► PostgreSQL Database
       │            - Users, Farms, Alerts
       │            - Weather Data
       │            - Activities
       │
       └─────────► OpenWeatherMap API
                    - Real-time Weather
                    - Historical Data
                    - Forecasts
```

## 🔧 Configuration

### Environment Variables

**Required:**
```env
APP_NAME=KLEMA
APP_ENV=local
APP_KEY=base64:...
APP_URL=http://localhost:8000

DB_CONNECTION=postgresql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=klema
DB_USERNAME=your_username
DB_PASSWORD=your_password

OPENWEATHER_API_KEY=your_openweather_api_key
VITE_OPENWEATHER_API_KEY=your_openweather_api_key
```

**Optional (Authentication):**
```env
SANCTUM_EXPIRATION=60                    # Token expiration (minutes)
SANCTUM_REMEMBER_EXPIRATION=43200        # Remember me expiration (minutes)
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
TRUSTED_PROXIES=*
```

**Mail (for email verification):**
```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@klema.local"
MAIL_FROM_NAME="${APP_NAME}"
```

### Database Configuration

KLEMA supports PostgreSQL (recommended), MySQL, and SQLite. Update `config/database.php` and `.env` accordingly.

## 🚀 Quick Start

See `setup_guide.txt` for detailed installation instructions.

**Quick commands:**
```bash
# Install dependencies
composer install
npm install
npm install three vanta

# Setup database
php artisan migrate
php artisan db:seed

# Start development servers
npm run dev        # Vite dev server (frontend)
php artisan serve  # Laravel server (backend)
```

## 🧪 Testing

Run PHPUnit tests:
```bash
php artisan test
```

Test files are located in `tests/Feature/` and `tests/Unit/`.

## 📝 API Documentation

All API endpoints require authentication via Bearer token (except login/register):
```
Authorization: Bearer {token}
```

Responses are JSON with `success` boolean and data fields.

## 🔄 Token Lifecycle

1. **Login:** User receives token with expiration
2. **Automatic Refresh:** Frontend rotates tokens before expiration
3. **Manual Refresh:** `POST /api/token/refresh`
4. **Device Management:** View/revoke tokens via Settings
5. **Logout:** Current token revoked, all tokens optional

## 👥 Target Users

- Local farmers & smallholder growers
- Agricultural cooperatives & extension officers
- LGUs & agricultural agencies
- NGOs & community organizations

## ✅ Expected Outcomes

- Farmers gain reliable, map-based dashboards for decision-making
- Centralized, secure storage of farm & weather data
- Alerts & recommendations to improve resilience against climate risks
- Accessible across desktop, tablet, and mobile devices
- Optional real-time consultations with agricultural specialists

## 👩‍💻 Contributors

- Ayuban, John Gabrielle A.
- Callanta, Yohan Lukin D.
- Galvarole, Sharlene Mae M.
- Lagrosas, Cydiemar O.
- Merto, Dara P.
- Panaque, Renelle Janos A.
- Polinar, Lean Emmaus L.

## 📄 License

MIT License

## 🔗 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [OpenWeatherMap API](https://openweathermap.org/api)
- [Leaflet Documentation](https://leafletjs.com/)
```

## setup_guide.txt

```txt:klema/setup_guide.txt
KLEMA Setup Guide
=================

This guide will walk you through setting up the KLEMA application on your local development environment.

Prerequisites
-------------
- PHP 8.2 or higher
- Composer (PHP package manager)
- Node.js 18+ and npm
- PostgreSQL (or MySQL/SQLite)
- OpenWeatherMap API key (get one at https://openweathermap.org/api)

Step 1: Clone the Repository
-----------------------------
If you haven't already, clone the repository:
    git clone <repository-url>
    cd sia-project-1/klema

Step 2: Install PHP Dependencies
--------------------------------
Install Laravel and PHP dependencies using Composer:
    composer install

Step 3: Install Node.js Dependencies
------------------------------------
Install frontend dependencies:
    npm install

Install additional required packages:
    npm install three vanta

Step 4: Environment Configuration
----------------------------------
Copy the environment file (if .env doesn't exist):
    cp .env.example .env

Edit the .env file and configure the following:

Required Settings:
    APP_NAME=KLEMA
    APP_ENV=local
    APP_URL=http://localhost:8000

Database Configuration (PostgreSQL example):
    DB_CONNECTION=postgresql
    DB_HOST=127.0.0.1
    DB_PORT=5432
    DB_DATABASE=klema
    DB_USERNAME=your_username
    DB_PASSWORD=your_password

OpenWeatherMap API Key:
    OPENWEATHER_API_KEY=your_api_key_here
    VITE_OPENWEATHER_API_KEY=your_api_key_here

Optional Authentication Settings:
    SANCTUM_EXPIRATION=60
    SANCTUM_REMEMBER_EXPIRATION=43200

Mail Configuration (for email verification):
    MAIL_MAILER=smtp
    MAIL_HOST=mailpit
    MAIL_PORT=1025
    MAIL_FROM_ADDRESS="noreply@klema.local"
    MAIL_FROM_NAME="KLEMA"

Step 5: Generate Application Key
-------------------------------
Generate the Laravel application encryption key:
    php artisan key:generate

Step 6: Database Setup
-----------------------
Create your database (PostgreSQL example):
    createdb klema

Or for MySQL:
    mysql -u root -p
    CREATE DATABASE klema;

Run migrations to create database tables:
    php artisan migrate

Seed the database with initial data:
    php artisan db:seed

Step 7: Storage Permissions
---------------------------
Ensure storage directories are writable:
    chmod -R 775 storage
    chmod -R 775 bootstrap/cache

Step 8: Start Development Servers
---------------------------------
You need to run two servers simultaneously:

Terminal 1 - Frontend (Vite):
    npm run dev

Terminal 2 - Backend (Laravel):
    php artisan serve

The application will be available at:
    Frontend: http://localhost:5173 (or port shown by Vite)
    Backend API: http://localhost:8000

Alternative: Use Laravel's built-in dev command (runs both):
    composer run dev

Step 9: Access the Application
-------------------------------
1. Open your browser to the frontend URL (usually http://localhost:5173)
2. Register a new account or use seeded test credentials
3. Verify your email (check mailpit or your configured mail service)
4. Login and start using KLEMA!

Troubleshooting
---------------

Issue: "Class not found" errors
Solution: Run `composer dump-autoload`

Issue: Database connection errors
Solution: 
    - Verify database credentials in .env
    - Ensure database server is running
    - Check database exists

Issue: OpenWeatherMap API errors
Solution:
    - Verify OPENWEATHER_API_KEY is set in .env
    - Ensure VITE_OPENWEATHER_API_KEY matches
    - Check API key is valid at openweathermap.org

Issue: Vite dev server not connecting
Solution:
    - Ensure npm run dev is running
    - Check vite.config.js is correct
    - Clear browser cache

Issue: Token authentication errors
Solution:
    - Clear browser localStorage
    - Check SANCTUM_STATEFUL_DOMAINS includes your domain
    - Verify APP_URL matches your frontend URL

Issue: Permission denied errors
Solution:
    - Run: chmod -R 775 storage bootstrap/cache
    - On Linux/Mac, may need sudo

Additional Commands
-------------------

Clear application cache:
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear

Run tests:
    php artisan test

Code style fix:
    ./vendor/bin/pint

Build for production:
    npm run build
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

Production Deployment Notes
----------------------------
1. Set APP_ENV=production
2. Set APP_DEBUG=false
3. Configure proper database credentials
4. Set up HTTPS (APP_URL=https://yourdomain.com)
5. Configure TRUSTED_PROXIES if behind a proxy
6. Set SESSION_SECURE_COOKIE=true
7. Run migrations: php artisan migrate --force
8. Build assets: npm run build
9. Optimize: php artisan optimize

For more information, see README.md
```

