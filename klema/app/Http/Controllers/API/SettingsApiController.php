<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingsApiController extends Controller
{
    /**
     * Get the authenticated user's settings.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            // Get or create user settings
            $userSettings = UserSettings::firstOrCreate(
                ['user_id' => $user->id],
                ['settings' => UserSettings::defaultSettings()]
            );

            return response()->json([
                'success' => true,
                'settings' => $userSettings->settings,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch user settings', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings.',
            ], 500);
        }
    }

    /**
     * Update the authenticated user's settings.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            // Validate the settings
            $validated = $request->validate([
                'locationType' => 'nullable|string|in:custom,farm',
                'defaultLocation' => 'nullable|string|max:255',
                'selectedFarmId' => 'nullable|string',
                'temperatureUnit' => 'nullable|string|in:celsius,fahrenheit',
                'windSpeedUnit' => 'nullable|string|in:ms,kmh,mph',
                'timeFormat' => 'nullable|string|in:24h,12h',
                'saveSearchHistory' => 'nullable|boolean',
                'anonymousUsageData' => 'nullable|boolean',
            ]);

            // Get or create user settings
            $userSettings = UserSettings::firstOrCreate(
                ['user_id' => $user->id],
                ['settings' => UserSettings::defaultSettings()]
            );

            // Merge with existing settings (only update provided fields)
            $currentSettings = $userSettings->settings;
            $updatedSettings = array_merge($currentSettings, array_filter($validated, fn($value) => $value !== null));

            // Update settings
            $userSettings->update([
                'settings' => $updatedSettings,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully.',
                'settings' => $userSettings->fresh()->settings,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to update user settings', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings.',
            ], 500);
        }
    }

    /**
     * Reset settings to defaults.
     */
    public function reset(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required.',
                ], 401);
            }

            // Get or create user settings
            $userSettings = UserSettings::firstOrCreate(
                ['user_id' => $user->id],
                ['settings' => UserSettings::defaultSettings()]
            );

            // Reset to defaults
            $userSettings->update([
                'settings' => UserSettings::defaultSettings(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings reset to defaults.',
                'settings' => $userSettings->fresh()->settings,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to reset user settings', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings.',
            ], 500);
        }
    }
}
