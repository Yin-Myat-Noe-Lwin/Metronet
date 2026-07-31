<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ServiceAreaRequest;
use App\Http\Requests\ServiceAreaUpdateRequest;
use App\Models\ServiceArea;
use Illuminate\Http\JsonResponse;
use App\Services\KafkaProducerService;
use Illuminate\Support\Facades\Log;

class ServiceAreaController extends Controller
{
    public function __construct(
        private KafkaProducerService $kafkaProducer
    ) {}

    public function store(ServiceAreaRequest $request)
    {
        try{
            $area = ServiceArea::create([
                        ...$request->validated(),
                        'status' => 1
                    ]);

            return response()->json([
                'message' => 'Service area created successfully',
                'data' => $area
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    public function index(): JsonResponse
    {
        try{
            $areas = ServiceArea::orderBy('created_at', 'desc')->get();

            return response()->json([
                'data' => $areas
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    // available service areas for customers to choose when subscribe a plan
    public function viewAreas(): JsonResponse
    {
        try {
            // Get all active service areas
            $areas = ServiceArea::where('status', 1)
                                    ->select('region', 'city', 'township')
                                    ->get();

            // region -> cities -> townships
            $hierarchy = [];

            foreach ($areas as $area) {
                // Initialize region if not exists
                if (!isset($hierarchy[$area->region])) {
                    $hierarchy[$area->region] = [];
                }

                // Initialize city if not exists under this region
                if (!isset($hierarchy[$area->region][$area->city])) {
                    $hierarchy[$area->region][$area->city] = [];
                }

                // Add township to city if not already added
                if (!in_array($area->township, $hierarchy[$area->region][$area->city])) {
                    $hierarchy[$area->region][$area->city][] = $area->township;
                }
            }

            Log::info('Area Relationship', $hierarchy);

            // extract all array keys (for regions)
            $regions = array_keys($hierarchy);

            $cities = [];
            $townships = [];

            foreach ($hierarchy as $region => $citiesData) {
                foreach ($citiesData as $city => $townshipsData) {
                    $cities[] = $city;
                    foreach ($townshipsData as $township) {
                        $townships[] = $township;
                    }
                }
            }

            return response()->json([
                'regions' => $regions,
                'cities' => array_unique($cities),
                'townships' => array_unique($townships),
                'hierarchy' => $hierarchy
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch service areas'
            ], 500);
        }
    }

    // get cities by region in dropdown
    public function getCitiesByRegion(Request $request): JsonResponse
    {
        try {
            $region = $request->query('region');

            if (!$region) {
                return response()->json([
                    'error' => 'Region parameter is required'
                ], 400);
            }

            $cities = ServiceArea::where('region', $region)
                                    ->where('status', 1)
                                    ->select('city')
                                    ->distinct()
                                    ->pluck('city')
                                    ->values()
                                    ->toArray();

            return response()->json([
                'data' => $cities
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching cities by region: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch cities'
            ], 500);
        }
    }

    // get townships by cities in dropdown bar
    public function getTownshipsByCity(Request $request): JsonResponse
    {
        try {
            $region = $request->query('region');
            $city = $request->query('city');

            if (!$region || !$city) {
                return response()->json([
                    'error' => 'Region and city parameters are required'
                ], 400);
            }

            $townships = ServiceArea::where('region', $region)
                                        ->where('city', $city)
                                        ->where('status', 1)
                                        ->select('township')
                                        ->distinct()
                                        ->pluck('township')
                                        ->values()
                                        ->toArray();

            return response()->json([
                'data' => $townships
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching townships by city: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch townships'
            ], 500);
        }
    }

    // filter township, city based on region
    public function getFilteredAreas(Request $request): JsonResponse
    {
        try {
            // get selected region
            $region = $request->query('region');
            // get selected city
            $city = $request->query('city');

            $query = ServiceArea::where('status', 1);

            if ($region) {
                $query->where('region', $region);
            }

            if ($city) {
                $query->where('city', $city);
            }

            $areas = $query->get();

            $response = [];

            if (!$region && !$city) {
                // Return all regions with their cities and townships
                $regions = ServiceArea::where('status', 1)
                                        ->select('region')
                                        ->distinct()
                                        ->pluck('region')
                                        ->toArray();

                foreach ($regions as $reg) {
                    $cities = ServiceArea::where('region', $reg)
                                            ->where('status', 1)
                                            ->select('city')
                                            ->distinct()
                                            ->pluck('city')
                                            ->toArray();

                    $response[$reg] = [];
                    foreach ($cities as $cit) {
                        $townships = ServiceArea::where('region', $reg)
                            ->where('city', $cit)
                            ->where('status', 1)
                            ->select('township')
                            ->distinct()
                            ->pluck('township')
                            ->toArray();

                        $response[$reg][$cit] = $townships;
                    }
                }

                return response()->json([
                    'data' => $response
                ]);
            }

            return response()->json([
                'data' => $areas
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching filtered areas: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch service areas'
            ], 500);
        }
    }

    public function update(ServiceAreaUpdateRequest $request, $id): JsonResponse
    {
        try {
            $area = ServiceArea::find($id);

            if (!$area) {
                return response()->json([
                    'message' => 'Not found'
                ]);
            }

            // Get old data before update
            $oldData = [
                'region' => $area->region,
                'city' => $area->city,
                'township' => $area->township,
                'status' => $area->status
            ];

            $area->update($request->validated());

            // Check what changed
            $regionChanged = $oldData['region'] != $area->region;
            $cityChanged = $oldData['city'] != $area->city;
            $townshipChanged = $oldData['township'] != $area->township;
            $statusChanged = $oldData['status'] != $area->status;

            $anyChange = $regionChanged || $cityChanged || $townshipChanged || $statusChanged;

            // Send notification via Kafka if any changes
            if ($anyChange) {
                try {
                    $statusLabels = [
                        0 => 'Inactive',
                        1 => 'Active'
                    ];

                    $this->kafkaProducer->publish(
                        config('kafka.consumers.service_area_updated.topic', 'service-area-updated'),
                        [
                            'service_area_id' => $area->id,
                            'region' => $area->region ?? 'N/A',
                            'city' => $area->city ?? 'N/A',
                            'township' => $area->township ?? 'N/A',
                            'old_region' => $oldData['region'],
                            'new_region' => $area->region,
                            'old_city' => $oldData['city'],
                            'new_city' => $area->city,
                            'old_township' => $oldData['township'],
                            'new_township' => $area->township,
                            'old_status' => $oldData['status'],
                            'new_status' => $area->status,
                            'old_status_label' => $statusLabels[$oldData['status']] ?? 'Unknown',
                            'new_status_label' => $statusLabels[$area->status] ?? 'Unknown',
                            'region_changed' => $regionChanged,
                            'city_changed' => $cityChanged,
                            'township_changed' => $townshipChanged,
                            'status_changed' => $statusChanged
                        ]
                    );

                    Log::info('Service area update notification published to Kafka', [
                        'service_area_id' => $area->id,
                        'changes' => [
                            'region' => $regionChanged,
                            'city' => $cityChanged,
                            'township' => $townshipChanged,
                            'status' => $statusChanged
                        ]
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to publish service area update: ' . $e->getMessage());
                }
            }

            return response()->json([
                'message' => 'Updated successfully',
                'data' => $area->fresh()
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    public function destroy($id)
    {
        try{
            $area = ServiceArea::find($id);

            if (!$area) {
                return response()->json([
                    'message' => 'Service area not found'
                ]);
            }

            $area->update([
                'status' => 0 // inactive
            ]);

            return response()->json([
                'message' => 'Service area disabled successfully'
            ]);
        }catch (PDOException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  $e->getMessage()
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }
}
