🌱 KLEMA – Climate-Smart Agriculture Monitoring System

KLEMA is a climate-smart agriculture platform designed to help farmers, cooperatives, and agricultural agencies adapt to the challenges of climate change. By combining real-time weather monitoring, interactive farm mapping, and data-driven recommendations, KLEMA empowers users to make informed farming decisions and reduce risks from extreme weather events.

Contributors

🌍 Introduction

Climate change impacts agriculture by altering weather patterns, reducing yields, and increasing risks from droughts, floods, and pests.
KLEMA provides reliable weather insights, farm visualization, and historical data analysis to strengthen decision-making and food security.

🎯 Objectives

Integrate real-time weather API data with historical climate analysis.

Provide interactive farm mapping with OpenStreetMap.

Support secure user authentication and role-based dashboards.

Deliver responsive dashboards accessible on desktop, tablet, and mobile.

Optionally, enable remote consultations with agricultural experts.

⚙️ Features
1. Weather Monitoring

Real-time weather (temperature, humidity, rainfall, wind).

Historical weather data storage for trend analysis.

Severe weather alerts.

2. Mapping Integration

Farm plots & boundaries visualized via OpenStreetMap.

Weather stations, cooperatives, and evacuation routes.

Mark/save/manage farm points of interest.

3. User Management

Email/login authentication (JWT).

Admin Dashboard: Manage users, monitor data, oversee system.

Farmer Dashboard: Weather updates, farm maps, reports.

4. Dashboard & Reporting

Mobile-first responsive dashboard.

Charts & tables for weekly/monthly trends.

Export weather/farm data (CSV with timestamps).

5. Communication Tools (Optional)

Video conferencing (Zoom/Google Meet API).

Screen sharing for training or consultations.

🛠 Tech Stack

Frontend: Vue.js (responsive dashboard), Leaflet/OpenLayers (map rendering).

Backend: Laravel (PHP, REST APIs).

Database: PostgreSQL (structured weather & user data).

Weather API: OpenWeather (real-time & historical data).

Mapping: OpenStreetMap tiles (farm visualization).

Authentication: JWT-based, with email verification.

Optional Communication: Zoom SDK / Google Meet API.

🏗 System Architecture

Frontend requests weather + map data →

Backend (Laravel REST API) retrieves weather info from OpenWeather & DB →

Database (PostgreSQL) stores historical weather & farm data →

Frontend (Vue.js + Leaflet) displays dashboard, charts, and maps.

🔬 Methodology

Data Collection – Connect to weather APIs; collect farm coordinates.

Database Management – Store users, farms, weather data, maps.

Backend Development – REST APIs for weather & mapping.

Frontend Development – Responsive dashboard with maps.

Testing & Deployment – Multi-device testing; deploy on cloud/university servers.

✅ Expected Outcomes

Farmers gain reliable, map-based dashboards for decision-making.

Centralized, secure storage of farm & weather data.

Alerts & recommendations to improve resilience against climate risks.

Accessible across desktop, tablet, and mobile devices.

Optional real-time consultations with agricultural specialists.

👥 Target Users

Local farmers & smallholder growers.

Agricultural cooperatives & extension officers.

LGUs & agricultural agencies.

NGOs & community organizations.

👩‍💻 Contributors

Ayuban, John Gabrielle A.

Callanta, Yohan Lukin D.

Galvarole, Sharlene Mae M.

Lagrosas, Cydiemar O.

Merto, Dara P.

Panaque, Renelle Janos A.

Polinar, Lean Emmaus L.