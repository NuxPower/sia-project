KLEMA_ROLE_BASED_FEATURES.txt

==================================================
SYSTEM OVERVIEW
- Provides role-based dashboards for administrators (data management) and farmers (information and recommendations)[cite: 54].
==================================================

[ROLE 1: ADMINISTRATOR]
Primary Focus: Data Management, System Oversight, and Reporting.

1.  SYSTEM REPORTING & EXPORT:
    - Handles data exports (PDF, CSV, XLSX)[cite: 572].
    - Provides API endpoints to export all farm data, weather data, and recorded farm activities[cite: 524].
    - Utilizes local storage or S3 integration for saving exported files[cite: 572].

2.  SYSTEM CONTROL & ANALYTICS:
    - Aggregates key system data for display in the admin dashboard[cite: 572].
    - Handles dashboard data and analytics[cite: 442].
    - Manually triggers weather updates from the external API[cite: 515].

3.  USER & ACCESS MANAGEMENT:
    - Manages login, registration, password resets, and user sessions[cite: 572].
    - Controls access permissions for different user roles via the Policy Layer[cite: 541].
    - Tracks and logs user actions or farm events for auditing and monitoring[cite: 572].

4.  ALERT MANAGEMENT:
    - Manages user notifications and system alerts based on weather or farm conditions[cite: 572].
    - Can create a new alert manually[cite: 520].

==================================================

[ROLE 2: FARMER]
Primary Focus: Information, Recommendations, and Farm-Level Data Entry[cite: 54].

1.  FARM MANAGEMENT (CRUD OPERATIONS):
    - Creates, Reads, Updates, and Deletes (CRUD) farm records[cite: 506].
    - Manages farm information and details[cite: 452].
    - Registers farm points, including latitude and longitude coordinates, for mapping[cite: 474, 511].

2.  WEATHER AND MAPPING INSIGHTS:
    - Accesses the map-based dashboard for farm visualization[cite: 54, 72].
    - Views farm locations on an interactive map[cite: 183].
    - Views real-time weather data, weather information, and alerts[cite: 181, 51].
    - Retrieves current weather and forecasts for informed decision-making[cite: 42, 211, 515].
    - Receives timely alerts and actionable recommendations to improve resilience against weather-related risks[cite: 586].
    - Can input or update records through forms on the user interface[cite: 182].

3.  ACTIVITY MANAGEMENT:
    - Records a new farm activity[cite: 520].
    - Retrieves current weather or farm alerts[cite: 520].

==================================================

[SHARED FEATURES]
- Login, Register, Logout[cite: 502].
- Retrieving the authenticated user profile[cite: 502].
- Viewing map-based and alert information[cite: 181].
- Retrieving past weather data stored in the database[cite: 515].