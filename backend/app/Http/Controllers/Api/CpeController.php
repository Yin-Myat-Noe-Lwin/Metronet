<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cpe;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\CpeUpdateRequest;
use App\Http\Requests\CpeRequest;
use PDOException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use App\Services\KafkaProducerService;
use Illuminate\Support\Facades\Log;

class CpeController extends Controller
{
    public function __construct(
        private KafkaProducerService $kafkaProducer
    ) {}

    public function index(): JsonResponse
    {
        try{
            $cpes = Cache::remember('cpes_list', 60 * 60, function () {
                return Cpe::orderBy('created_at', 'desc')->get();
            });

            return response()->json([
                'data' => $cpes
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

    public function store(CpeRequest $request)
    {
        try{
            $cpe = Cpe::create([
                'serial_number' => $request->serial_number,
                'mac_address'   => $request->mac_address,
                'status'        => 0
            ]);

            Cache::forget('cpes_list');

            return response()->json([
                'message' => 'CPE created successfully',
                'data' => $cpe
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

    public function update(CpeUpdateRequest $request, $id): JsonResponse
    {
        try {
            $cpe = Cpe::find($id);

            if (!$cpe) {
                return response()->json([
                    'message' => 'CPE not found'
                ]);
            }

            // Get old data before update
            $oldData = [
                'serial_number' => $cpe->serial_number,
                'mac_address' => $cpe->mac_address,
                'status' => $cpe->status
            ];

            $cpe->update($request->validated());

            Cache::forget('cpes_list');

            // Check what changed
            $serialChanged = $oldData['serial_number'] != $cpe->serial_number;
            $macChanged = $oldData['mac_address'] != $cpe->mac_address;
            $statusChanged = $oldData['status'] != $cpe->status;

            $anyChange = $serialChanged || $macChanged || $statusChanged;

            // Send notification via Kafka if any changes
            if ($anyChange) {
                try {
                    $this->kafkaProducer->publish(
                        config('kafka.consumers.cpe_updated.topic'),
                        [
                            'cpe_id' => $cpe->id,
                            'serial_number' => $cpe->serial_number ?? 'N/A',
                            'mac_address' => $cpe->mac_address ?? 'N/A',
                            'old_serial_number' => $oldData['serial_number'],
                            'new_serial_number' => $cpe->serial_number,
                            'old_mac_address' => $oldData['mac_address'],
                            'new_mac_address' => $cpe->mac_address,
                            'old_status' => $oldData['status'],
                            'new_status' => $cpe->status,
                            'old_status_label' => $this->getStatusLabel($oldData['status']),
                            'new_status_label' => $this->getStatusLabel($cpe->status),
                            'serial_changed' => $serialChanged,
                            'mac_changed' => $macChanged,
                            'status_changed' => $statusChanged,
                            'timestamp' => now()->toIso8601String()
                        ]
                    );

                    Log::info('CPE update notification published to Kafka', [
                        'cpe_id' => $cpe->id,
                        'changes' => [
                            'serial_number' => $serialChanged,
                            'mac_address' => $macChanged,
                            'status' => $statusChanged
                        ]
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to publish CPE update: ' . $e->getMessage());
                }
            }

            return response()->json([
                'message' => 'CPE updated successfully',
                'data' => $cpe
            ]);

        } catch (PDOException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ]);
        }
    }

    // Helper method for status labels
    private function getStatusLabel($status): string
    {
        $labels = [
            0 => 'Available',
            1 => 'Assigned',
            2 => 'Faulty',
            3 => 'Maintenance',
            4 => 'Retired'
        ];

        return $labels[$status] ?? 'Unknown';
    }

    public function destroy($id): JsonResponse
    {
        $cpe = Cpe::where('id', $id)->first();

        // if cpe assigned, can't delete
        if ($cpe->status == 1) {
            return response()->json([
                'message' => 'CPE is assigned and cannot be deleted'
            ]);
        }

        $cpe->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}
