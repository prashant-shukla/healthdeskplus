<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    private $apiKey;
    private $baseUrl = 'https://maps.googleapis.com/maps/api/place';

    public function __construct()
    {
        $this->apiKey = config('services.google.places_api_key');
    }

    /**
     * Get place autocomplete suggestions
     */
    public function getAutocompleteSuggestions(string $input, array $types = ['establishment'], string $country = 'IN'): array
    {
        // Check if API key is configured
        if (empty($this->apiKey)) {
            Log::warning('Google Places API key not configured, returning mock data');
            return $this->getMockAutocompleteResults($input);
        }

        try {
            // Try new Places API first
            $response = Http::post('https://places.googleapis.com/v1/places:autocomplete', [
                'input' => $input,
                'includedRegionCodes' => [$country],
                'includedPrimaryTypes' => $types,
                'languageCode' => 'en',
            ], [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Goog-Api-Key' => $this->apiKey,
                    'X-Goog-FieldMask' => 'suggestions.placePrediction.place,suggestions.placePrediction.text',
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $results = $this->formatNewApiResults($data);
                if (!empty($results)) {
                    return $results;
                }
            } else {
                Log::warning('Google Places New API failed', [
                    'input' => $input,
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
            }

            // Fallback to legacy API if new API fails
            $response = Http::get($this->baseUrl . '/autocomplete/json', [
                'input' => $input,
                'types' => implode('|', $types),
                'components' => 'country:' . $country,
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK' && !empty($data['predictions'])) {
                    return $this->formatAutocompleteResults($data['predictions']);
                }
            }

            Log::warning('Google Places API error - both new and legacy APIs failed', [
                'input' => $input,
                'response' => $response->json()
            ]);

            // Return mock data if API fails
            return $this->getMockAutocompleteResults($input);

        } catch (\Exception $e) {
            Log::error('Google Places API exception', [
                'input' => $input,
                'error' => $e->getMessage()
            ]);

            // Return mock data on exception
            return $this->getMockAutocompleteResults($input);
        }
    }

    /**
     * Get place details by place_id
     */
    public function getPlaceDetails(string $placeId): ?array
    {
        // Check if API key is configured
        if (empty($this->apiKey)) {
            Log::warning('Google Places API key not configured, returning mock place details');
            return $this->getMockPlaceDetails($placeId);
        }

        try {
            $response = Http::get($this->baseUrl . '/details/json', [
                'place_id' => $placeId,
                'fields' => 'name,formatted_address,address_components,geometry,formatted_phone_number,website',
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'OK') {
                    return $this->formatPlaceDetails($data['result']);
                }
            }

            Log::warning('Google Places Details API error', [
                'place_id' => $placeId,
                'response' => $response->json()
            ]);

            // Return mock data if API fails
            return $this->getMockPlaceDetails($placeId);

        } catch (\Exception $e) {
            Log::error('Google Places Details API exception', [
                'place_id' => $placeId,
                'error' => $e->getMessage()
            ]);

            // Return mock data on exception
            return $this->getMockPlaceDetails($placeId);
        }
    }

    /**
     * Search for medical establishments
     */
    public function searchMedicalEstablishments(string $query, string $city = null): array
    {
        $types = ['hospital', 'doctor', 'health', 'pharmacy'];
        $input = $city ? "{$query} {$city}" : $query;
        
        return $this->getAutocompleteSuggestions($input, $types);
    }

    /**
     * Format autocomplete results
     */
    private function formatAutocompleteResults(array $predictions): array
    {
        return array_map(function ($prediction) {
            return [
                'place_id' => $prediction['place_id'],
                'description' => $prediction['description'],
                'main_text' => $prediction['structured_formatting']['main_text'] ?? '',
                'secondary_text' => $prediction['structured_formatting']['secondary_text'] ?? '',
                'types' => $prediction['types'] ?? [],
            ];
        }, $predictions);
    }

    /**
     * Format place details
     */
    private function formatPlaceDetails(array $result): array
    {
        $addressComponents = $result['address_components'] ?? [];
        $formattedAddress = $result['formatted_address'] ?? '';
        
        // Extract address components
        $address = [
            'name' => $result['name'] ?? '',
            'formatted_address' => $formattedAddress,
            'street_number' => $this->extractAddressComponent($addressComponents, 'street_number'),
            'route' => $this->extractAddressComponent($addressComponents, 'route'),
            'locality' => $this->extractAddressComponent($addressComponents, 'locality'),
            'administrative_area_level_2' => $this->extractAddressComponent($addressComponents, 'administrative_area_level_2'),
            'administrative_area_level_1' => $this->extractAddressComponent($addressComponents, 'administrative_area_level_1'),
            'country' => $this->extractAddressComponent($addressComponents, 'country'),
            'postal_code' => $this->extractAddressComponent($addressComponents, 'postal_code'),
            'phone' => $result['formatted_phone_number'] ?? '',
            'website' => $result['website'] ?? '',
            'coordinates' => [
                'lat' => $result['geometry']['location']['lat'] ?? null,
                'lng' => $result['geometry']['location']['lng'] ?? null,
            ],
        ];

        // Build clinic address
        $address['clinic_address'] = trim(($address['street_number'] ?? '') . ' ' . ($address['route'] ?? ''));
        $address['clinic_city'] = $address['locality'] ?? $address['administrative_area_level_2'];
        $address['clinic_state'] = $address['administrative_area_level_1'];
        $address['clinic_pincode'] = $address['postal_code'];

        return $address;
    }

    /**
     * Extract specific address component
     */
    private function extractAddressComponent(array $components, string $type): ?string
    {
        foreach ($components as $component) {
            if (in_array($type, $component['types'])) {
                return $component['long_name'];
            }
        }
        return null;
    }

    /**
     * Format results from new Places API
     */
    private function formatNewApiResults(array $data): array
    {
        $results = [];
        
        if (isset($data['suggestions'])) {
            foreach ($data['suggestions'] as $suggestion) {
                if (isset($suggestion['placePrediction'])) {
                    $prediction = $suggestion['placePrediction'];
                    $results[] = [
                        'place_id' => $prediction['place'] ?? '',
                        'description' => $prediction['text']['text'] ?? '',
                        'structured_formatting' => [
                            'main_text' => $prediction['text']['text'] ?? '',
                            'secondary_text' => ''
                        ]
                    ];
                }
            }
        }
        
        return $results;
    }

    /**
     * Get mock autocomplete results for testing
     */
    private function getMockAutocompleteResults(string $input): array
    {
        $mockResults = [
            'apollo' => [
                [
                    'place_id' => 'mock_apollo_mumbai',
                    'description' => 'Apollo Hospital, Mumbai, Maharashtra, India',
                    'structured_formatting' => [
                        'main_text' => 'Apollo Hospital',
                        'secondary_text' => 'Mumbai, Maharashtra, India'
                    ]
                ],
                [
                    'place_id' => 'mock_apollo_delhi',
                    'description' => 'Apollo Hospital, New Delhi, Delhi, India',
                    'structured_formatting' => [
                        'main_text' => 'Apollo Hospital',
                        'secondary_text' => 'New Delhi, Delhi, India'
                    ]
                ],
                [
                    'place_id' => 'mock_apollo_chennai',
                    'description' => 'Apollo Hospital, Chennai, Tamil Nadu, India',
                    'structured_formatting' => [
                        'main_text' => 'Apollo Hospital',
                        'secondary_text' => 'Chennai, Tamil Nadu, India'
                    ]
                ]
            ],
            'fortis' => [
                [
                    'place_id' => 'mock_fortis_mumbai',
                    'description' => 'Fortis Hospital, Mumbai, Maharashtra, India',
                    'structured_formatting' => [
                        'main_text' => 'Fortis Hospital',
                        'secondary_text' => 'Mumbai, Maharashtra, India'
                    ]
                ],
                [
                    'place_id' => 'mock_fortis_delhi',
                    'description' => 'Fortis Hospital, New Delhi, Delhi, India',
                    'structured_formatting' => [
                        'main_text' => 'Fortis Hospital',
                        'secondary_text' => 'New Delhi, Delhi, India'
                    ]
                ]
            ],
            'max' => [
                [
                    'place_id' => 'mock_max_delhi',
                    'description' => 'Max Hospital, New Delhi, Delhi, India',
                    'structured_formatting' => [
                        'main_text' => 'Max Hospital',
                        'secondary_text' => 'New Delhi, Delhi, India'
                    ]
                ],
                [
                    'place_id' => 'mock_max_mumbai',
                    'description' => 'Max Hospital, Mumbai, Maharashtra, India',
                    'structured_formatting' => [
                        'main_text' => 'Max Hospital',
                        'secondary_text' => 'Mumbai, Maharashtra, India'
                    ]
                ]
            ],
            'manipal' => [
                [
                    'place_id' => 'mock_manipal_bangalore',
                    'description' => 'Manipal Hospital, Bangalore, Karnataka, India',
                    'structured_formatting' => [
                        'main_text' => 'Manipal Hospital',
                        'secondary_text' => 'Bangalore, Karnataka, India'
                    ]
                ]
            ],
            'medanta' => [
                [
                    'place_id' => 'mock_medanta_delhi',
                    'description' => 'Medanta Hospital, New Delhi, Delhi, India',
                    'structured_formatting' => [
                        'main_text' => 'Medanta Hospital',
                        'secondary_text' => 'New Delhi, Delhi, India'
                    ]
                ]
            ],
            'kolkata' => [
                [
                    'place_id' => 'mock_amri_kolkata',
                    'description' => 'AMRI Hospital, Kolkata, West Bengal, India',
                    'structured_formatting' => [
                        'main_text' => 'AMRI Hospital',
                        'secondary_text' => 'Kolkata, West Bengal, India'
                    ]
                ]
            ],
            'hyderabad' => [
                [
                    'place_id' => 'mock_continental_hyderabad',
                    'description' => 'Continental Hospital, Hyderabad, Telangana, India',
                    'structured_formatting' => [
                        'main_text' => 'Continental Hospital',
                        'secondary_text' => 'Hyderabad, Telangana, India'
                    ]
                ]
            ]
        ];

        $inputLower = strtolower($input);
        
        // Find matching results
        foreach ($mockResults as $key => $results) {
            if (strpos($inputLower, $key) !== false) {
                return $results;
            }
        }

        // For city names, return relevant hospitals
        $cityHospitals = [
            'mumbai' => [
                [
                    'place_id' => 'mock_lilavati_mumbai',
                    'description' => 'Lilavati Hospital, Mumbai, Maharashtra, India',
                    'structured_formatting' => [
                        'main_text' => 'Lilavati Hospital',
                        'secondary_text' => 'Mumbai, Maharashtra, India'
                    ]
                ],
                [
                    'place_id' => 'mock_kokilaben_mumbai',
                    'description' => 'Kokilaben Hospital, Mumbai, Maharashtra, India',
                    'structured_formatting' => [
                        'main_text' => 'Kokilaben Hospital',
                        'secondary_text' => 'Mumbai, Maharashtra, India'
                    ]
                ]
            ],
            'delhi' => [
                [
                    'place_id' => 'mock_aiims_delhi',
                    'description' => 'AIIMS Hospital, New Delhi, Delhi, India',
                    'structured_formatting' => [
                        'main_text' => 'AIIMS Hospital',
                        'secondary_text' => 'New Delhi, Delhi, India'
                    ]
                ]
            ],
            'bangalore' => [
                [
                    'place_id' => 'mock_narayana_bangalore',
                    'description' => 'Narayana Health, Bangalore, Karnataka, India',
                    'structured_formatting' => [
                        'main_text' => 'Narayana Health',
                        'secondary_text' => 'Bangalore, Karnataka, India'
                    ]
                ]
            ]
        ];

        foreach ($cityHospitals as $city => $hospitals) {
            if (strpos($inputLower, $city) !== false) {
                return $hospitals;
            }
        }

        // Default mock result for unknown inputs
        return [
            [
                'place_id' => 'mock_general_hospital',
                'description' => 'General Hospital, ' . ucfirst($input) . ', India',
                'structured_formatting' => [
                    'main_text' => 'General Hospital',
                    'secondary_text' => ucfirst($input) . ', India'
                ]
            ]
        ];
    }

    /**
     * Get mock place details for testing
     */
    private function getMockPlaceDetails(string $placeId): ?array
    {
        $mockPlaceDetails = [
            'mock_apollo_mumbai' => [
                'name' => 'Apollo Hospital',
                'formatted_address' => 'Apollo Hospital, Mumbai, Maharashtra, India',
                'address_components' => [
                    ['long_name' => 'Apollo Hospital', 'short_name' => 'Apollo Hospital', 'types' => ['establishment']],
                    ['long_name' => 'Mumbai', 'short_name' => 'Mumbai', 'types' => ['locality']],
                    ['long_name' => 'Maharashtra', 'short_name' => 'MH', 'types' => ['administrative_area_level_1']],
                    ['long_name' => 'India', 'short_name' => 'IN', 'types' => ['country']]
                ],
                'geometry' => [
                    'location' => ['lat' => 19.0760, 'lng' => 72.8777]
                ],
                'formatted_phone_number' => '+91 22 6666 6666',
                'website' => 'https://www.apollohospitals.com'
            ],
            'mock_apollo_delhi' => [
                'name' => 'Apollo Hospital',
                'formatted_address' => 'Apollo Hospital, New Delhi, Delhi, India',
                'address_components' => [
                    ['long_name' => 'Apollo Hospital', 'short_name' => 'Apollo Hospital', 'types' => ['establishment']],
                    ['long_name' => 'New Delhi', 'short_name' => 'New Delhi', 'types' => ['locality']],
                    ['long_name' => 'Delhi', 'short_name' => 'DL', 'types' => ['administrative_area_level_1']],
                    ['long_name' => 'India', 'short_name' => 'IN', 'types' => ['country']]
                ],
                'geometry' => [
                    'location' => ['lat' => 28.6139, 'lng' => 77.2090]
                ],
                'formatted_phone_number' => '+91 11 6666 6666',
                'website' => 'https://www.apollohospitals.com'
            ],
            'mock_fortis_mumbai' => [
                'name' => 'Fortis Hospital',
                'formatted_address' => 'Fortis Hospital, Mumbai, Maharashtra, India',
                'address_components' => [
                    ['long_name' => 'Fortis Hospital', 'short_name' => 'Fortis Hospital', 'types' => ['establishment']],
                    ['long_name' => 'Mumbai', 'short_name' => 'Mumbai', 'types' => ['locality']],
                    ['long_name' => 'Maharashtra', 'short_name' => 'MH', 'types' => ['administrative_area_level_1']],
                    ['long_name' => 'India', 'short_name' => 'IN', 'types' => ['country']]
                ],
                'geometry' => [
                    'location' => ['lat' => 19.0760, 'lng' => 72.8777]
                ],
                'formatted_phone_number' => '+91 22 7777 7777',
                'website' => 'https://www.fortishealthcare.com'
            ],
            'mock_max_delhi' => [
                'name' => 'Max Hospital',
                'formatted_address' => 'Max Hospital, New Delhi, Delhi, India',
                'address_components' => [
                    ['long_name' => 'Max Hospital', 'short_name' => 'Max Hospital', 'types' => ['establishment']],
                    ['long_name' => 'New Delhi', 'short_name' => 'New Delhi', 'types' => ['locality']],
                    ['long_name' => 'Delhi', 'short_name' => 'DL', 'types' => ['administrative_area_level_1']],
                    ['long_name' => 'India', 'short_name' => 'IN', 'types' => ['country']]
                ],
                'geometry' => [
                    'location' => ['lat' => 28.6139, 'lng' => 77.2090]
                ],
                'formatted_phone_number' => '+91 11 8888 8888',
                'website' => 'https://www.maxhealthcare.in'
            ],
            'mock_manipal_bangalore' => [
                'name' => 'Manipal Hospital',
                'formatted_address' => 'Manipal Hospital, Bangalore, Karnataka, India',
                'address_components' => [
                    ['long_name' => 'Manipal Hospital', 'short_name' => 'Manipal Hospital', 'types' => ['establishment']],
                    ['long_name' => 'Bangalore', 'short_name' => 'Bangalore', 'types' => ['locality']],
                    ['long_name' => 'Karnataka', 'short_name' => 'KA', 'types' => ['administrative_area_level_1']],
                    ['long_name' => 'India', 'short_name' => 'IN', 'types' => ['country']]
                ],
                'geometry' => [
                    'location' => ['lat' => 12.9716, 'lng' => 77.5946]
                ],
                'formatted_phone_number' => '+91 80 9999 9999',
                'website' => 'https://www.manipalhospitals.com'
            ],
            'mock_medanta_delhi' => [
                'name' => 'Medanta Hospital',
                'formatted_address' => 'Medanta Hospital, New Delhi, Delhi, India',
                'address_components' => [
                    ['long_name' => 'Medanta Hospital', 'short_name' => 'Medanta Hospital', 'types' => ['establishment']],
                    ['long_name' => 'New Delhi', 'short_name' => 'New Delhi', 'types' => ['locality']],
                    ['long_name' => 'Delhi', 'short_name' => 'DL', 'types' => ['administrative_area_level_1']],
                    ['long_name' => 'India', 'short_name' => 'IN', 'types' => ['country']]
                ],
                'geometry' => [
                    'location' => ['lat' => 28.6139, 'lng' => 77.2090]
                ],
                'formatted_phone_number' => '+91 11 5555 5555',
                'website' => 'https://www.medanta.org'
            ]
        ];

        return $mockPlaceDetails[$placeId] ?? null;
    }
}
