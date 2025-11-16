<?php

return [
    'fallback_location' => [
        'label' => 'Butuan City, PH',
        'lat' => 8.9475,
        'lon' => 125.5406,
    ],

    'types' => [
        'planting' => [
            'label' => 'Planting',
            'description' => 'Seedbed preparation, direct seeding, or transplanting.',
            'default_actions' => [
                'Prepare seedbeds and tools a day in advance.',
                'Irrigate lightly if soil moisture is below target.',
            ],
            'alert_tags' => ['weather', 'irrigation'],
            'rules' => [
                [
                    'metric' => 'precip_mm',
                    'operator' => '>=',
                    'value' => 12,
                    'severity' => 'delay',
                    'message' => 'Heavy rainfall (~{precip_mm} mm) will waterlog seedbeds.',
                ],
                [
                    'metric' => 'precip_mm',
                    'operator' => '>=',
                    'value' => 5,
                    'severity' => 'caution',
                    'message' => 'Light rain (~{precip_mm} mm) expected — plan drainage.',
                ],
                [
                    'metric' => 'wind_max_kmh',
                    'operator' => '>=',
                    'value' => 35,
                    'severity' => 'caution',
                    'message' => 'Gusts up to {wind_max_kmh} km/h — protect young seedlings.',
                ],
                [
                    'metric' => 'temp_min',
                    'operator' => '<=',
                    'value' => 18,
                    'severity' => 'caution',
                    'message' => 'Low temperatures (~{temp_min}°C) can slow germination.',
                ],
            ],
            'tips' => [
                'go' => 'Weather window looks good for planting.',
                'caution' => 'Planting is possible with precautions.',
                'delay' => 'Delay planting until conditions stabilize.',
                'unknown' => 'Weather data unavailable — rely on on-site observations.',
            ],
        ],
        'irrigation' => [
            'label' => 'Irrigation',
            'description' => 'Scheduling irrigation or fertigation passes.',
            'default_actions' => [
                'Inspect pumps and mainlines before pressurizing.',
                'Log actual water applied for each block.',
            ],
            'alert_tags' => ['irrigation', 'maintenance'],
            'rules' => [
                [
                    'metric' => 'precip_mm',
                    'operator' => '>=',
                    'value' => 6,
                    'severity' => 'delay',
                    'message' => 'Incoming rainfall (~{precip_mm} mm) can replace irrigation.',
                ],
                [
                    'metric' => 'precip_probability',
                    'operator' => '>=',
                    'value' => 70,
                    'severity' => 'caution',
                    'message' => 'High rain probability ({precip_probability}%) — keep plan flexible.',
                ],
                [
                    'metric' => 'wind_max_kmh',
                    'operator' => '>=',
                    'value' => 40,
                    'severity' => 'caution',
                    'message' => 'High winds ({wind_max_kmh} km/h) may disrupt sprinkler uniformity.',
                ],
            ],
            'tips' => [
                'go' => 'Great window for irrigation.',
                'caution' => 'Irrigate with contingencies (watch forecasts/alerts).',
                'delay' => 'Skip irrigation and reassess after rainfall.',
                'unknown' => 'Weather data missing — check field sensors before irrigating.',
            ],
        ],
        'harvesting' => [
            'label' => 'Harvesting',
            'description' => 'Cutting, threshing, or hauling operations.',
            'default_actions' => [
                'Schedule transport ahead of the dry window.',
                'Sharpen harvest tools and prep drying area.',
            ],
            'alert_tags' => ['harvest', 'weather'],
            'rules' => [
                [
                    'metric' => 'precip_mm',
                    'operator' => '>=',
                    'value' => 3,
                    'severity' => 'delay',
                    'message' => 'Rainfall (~{precip_mm} mm) will raise grain moisture.',
                ],
                [
                    'metric' => 'wind_max_kmh',
                    'operator' => '>=',
                    'value' => 45,
                    'severity' => 'caution',
                    'message' => 'Strong gusts ({wind_max_kmh} km/h) make harvesting risky.',
                ],
                [
                    'metric' => 'temp_max',
                    'operator' => '<',
                    'value' => 20,
                    'severity' => 'caution',
                    'message' => 'Cool highs (~{temp_max}°C) slow drying after harvest.',
                ],
            ],
            'tips' => [
                'go' => 'Dry window available for harvesting.',
                'caution' => 'Harvest with contingency plans.',
                'delay' => 'Postpone harvesting to avoid quality losses.',
                'unknown' => 'Weather uncertain — scout fields before cutting.',
            ],
        ],
        'fertilizing' => [
            'label' => 'Fertilizing',
            'description' => 'Broadcast or foliar fertilizer application.',
            'default_actions' => [
                'Calibrate spreaders or sprayers before application.',
                'Keep fertilizer dry and protected from clumping.',
            ],
            'alert_tags' => ['weather', 'maintenance'],
            'rules' => [
                [
                    'metric' => 'precip_mm',
                    'operator' => '>=',
                    'value' => 10,
                    'severity' => 'delay',
                    'message' => 'Heavy rain (~{precip_mm} mm) may leach nutrients.',
                ],
                [
                    'metric' => 'wind_max_kmh',
                    'operator' => '>=',
                    'value' => 30,
                    'severity' => 'caution',
                    'message' => 'Wind ({wind_max_kmh} km/h) reduces application accuracy.',
                ],
                [
                    'metric' => 'humidity_avg',
                    'operator' => '>=',
                    'value' => 90,
                    'severity' => 'caution',
                    'message' => 'High humidity ({humidity_avg}%) may cause foliar burn.',
                ],
            ],
            'tips' => [
                'go' => 'Fertilizer application window is open.',
                'caution' => 'Apply with protective measures.',
                'delay' => 'Hold fertilizer to avoid waste or burn.',
                'unknown' => 'Weather data unavailable — confirm field conditions first.',
            ],
        ],
        'pest_control' => [
            'label' => 'Pest & Disease Control',
            'description' => 'Spraying pesticides or fungicides.',
            'default_actions' => [
                'Confirm pest threshold scouting data.',
                'Check PPE and nozzle condition before mixing.',
            ],
            'alert_tags' => ['maintenance', 'weather'],
            'rules' => [
                [
                    'metric' => 'wind_max_kmh',
                    'operator' => '>=',
                    'value' => 25,
                    'severity' => 'delay',
                    'message' => 'Wind ({wind_max_kmh} km/h) will cause chemical drift.',
                ],
                [
                    'metric' => 'precip_probability',
                    'operator' => '>=',
                    'value' => 60,
                    'severity' => 'delay',
                    'message' => 'Rain chance ({precip_probability}%) risks product wash-off.',
                ],
                [
                    'metric' => 'temp_max',
                    'operator' => '>=',
                    'value' => 34,
                    'severity' => 'caution',
                    'message' => 'High temps (~{temp_max}°C) increase volatilization.',
                ],
            ],
            'tips' => [
                'go' => 'Conditions look stable for spraying.',
                'caution' => 'Spray with drift buffers and monitor weather.',
                'delay' => 'Pause spraying until conditions improve.',
                'unknown' => 'No forecast data — rely on on-site weather sensors.',
            ],
        ],
    ],
];


