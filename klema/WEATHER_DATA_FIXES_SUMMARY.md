# Weather Data Fixes Summary

## Issues Fixed

### 1. ✅ Date Filtering - Allow Today's Data
**Problem**: System excluded today's data from historical queries, causing "no data available" even when data existed for today.

**Fix**: Modified date filtering to include today's data while still excluding future dates:
- Changed `where('recorded_at', '<', $todayUtc)` to `where('recorded_at', '<', $tomorrowUtc)`
- Added date range filter: `where('recorded_at', '>=', $todayUtc->copy()->subDays($days))`
- Updated comments to reflect that today's data is included

**Files Changed**:
- `klema/app/Services/WeatherService.php`:
  - `getHistoricalWeather()` - lines 118-130
  - `getHistoricalWeatherByCoordinates()` - lines 150-164
- `klema/app/Http/Controllers/API/WeatherApiController.php`:
  - `getWeatherHistory()` - lines 110-126
  - `getStoredWeatherHistory()` - lines 397-405

### 2. ✅ Farm ID Support in History API
**Problem**: History API endpoints didn't support querying by `farm_id`, only by location name or coordinates. This meant farm-specific weather data wasn't retrievable via history API.

**Fix**: Added `farm_id` parameter to history API and included it in queries:
- Added `$farmId = $request->get('farm_id');` to `getWeatherHistory()`
- Updated `getStoredWeatherHistory()` to accept `farm_id` parameter
- Added farm_id matching in query logic with priority: farm_id > location > coordinates

**Files Changed**:
- `klema/app/Http/Controllers/API/WeatherApiController.php`:
  - `getWeatherHistory()` - line 98
  - `getStoredWeatherHistory()` - lines 395-454

### 3. ✅ Improved Location Matching
**Problem**: Location matching was too restrictive - records with `farm_id` weren't matched when querying by location/coordinates, and records with NULL values in some fields were excluded.

**Fix**: Made location matching more flexible:
- Changed query logic to use OR conditions: match by farm_id OR location_name OR coordinates
- Records can now match if ANY identifier (farm_id, location_name, or coordinates) matches
- Added explicit NULL checks for latitude/longitude when querying by coordinates
- Removed requirement for location_name to be NOT NULL when querying by coordinates

**Files Changed**:
- `klema/app/Http/Controllers/API/WeatherApiController.php`:
  - `getStoredWeatherHistory()` - lines 412-454

## Remaining Issues

### 1. ⚠️ Future Dates Still Excluded
**Issue**: Records with `recorded_at='2025-11-12 00:00:00'` will still be excluded if today is before November 12, 2025. This is by design - the system excludes future dates to prevent displaying data that hasn't happened yet.

**Recommendation**: 
- If data has future dates for testing purposes, update the `recorded_at` dates to be in the past or today
- Or modify the system to allow future dates in development/testing environments
- Check system clock to ensure it's set correctly

### 2. ⚠️ Null Value Handling
**Issue**: Many records have NULL values for `humidity`, `farm_id`, or `location_name`. The system handles these gracefully, but records with NULL `location_name` and NULL `farm_id` can only match by coordinates.

**Current Behavior**: 
- Records with NULL `location_name` can still match by coordinates
- Records with NULL `farm_id` can still match by location_name or coordinates
- Records with NULL coordinates can match by `farm_id` or `location_name`
- Records with all identifiers NULL cannot be matched (by design)

**Recommendation**: Ensure data has at least one identifier (farm_id, location_name, or coordinates) populated for reliable matching.

### 3. ⚠️ Location Name Case Sensitivity
**Issue**: Location names are stored in lowercase (`'managok farm'`), and the system normalizes query locations to lowercase. This should work correctly, but inconsistencies in location names might cause mismatches.

**Current Behavior**: 
- Location names are normalized to lowercase before matching
- Whitespace is trimmed
- Matching should work correctly if location names match exactly (after normalization)

**Recommendation**: 
- Verify location names in database match query locations (after normalization)
- Consider fuzzy matching or location name aliases for better matching

## Testing Recommendations

1. **Test with SQL Data**: Import `weather_data.sql` and verify records are retrievable:
   ```sql
   -- Test query by location name
   SELECT * FROM weather_data WHERE location_name = 'managok farm';
   
   -- Test query by coordinates
   SELECT * FROM weather_data WHERE latitude BETWEEN 8.025081 - 0.1 AND 8.025081 + 0.1
     AND longitude BETWEEN 125.197105 - 0.1 AND 125.197105 + 0.1;
   
   -- Test query by farm_id
   SELECT * FROM weather_data WHERE farm_id = 3;
   ```

2. **Test Date Filtering**: Verify today's data is included but future dates are excluded:
   ```php
   // Should include today's data
   $today = Carbon::today('UTC');
   $records = WeatherData::where('recorded_at', '>=', $today->copy()->subDays(7))
       ->where('recorded_at', '<', $today->copy()->addDay())
       ->get();
   ```

3. **Test Location Matching**: Verify records match by farm_id, location_name, or coordinates:
   ```php
   // Should match record with farm_id=3, location_name='managok farm', and coordinates
   $history = $this->getStoredWeatherHistory('managok farm', null, null, null, 30);
   $historyByFarm = $this->getStoredWeatherHistory(null, null, null, 3, 30);
   $historyByCoords = $this->getStoredWeatherHistory(null, 8.025081, 125.197105, null, 30);
   ```

4. **Test API Endpoints**: Verify history API returns data:
   ```bash
   # Test by location
   GET /api/weather/history?location=managok%20farm&days=30
   
   # Test by coordinates
   GET /api/weather/history?lat=8.025081&lon=125.197105&days=30
   
   # Test by farm_id
   GET /api/weather/history?farm_id=3&days=30
   ```

## Next Steps

1. ✅ Test fixes with actual SQL data
2. ⏳ Verify location names match exactly after normalization
3. ⏳ Check system clock to ensure correct date
4. ⏳ Update future-dated records if needed
5. ⏳ Monitor API responses to ensure data is being returned correctly

## Notes

- The system now allows today's data in historical queries, which should fix the "no data available" issue for today's records
- Farm ID support enables querying weather data by farm, which is important for farm-specific features
- Flexible location matching ensures records are found even if some identifying fields are NULL
- Future dates are still excluded by design to prevent displaying data that hasn't happened yet

