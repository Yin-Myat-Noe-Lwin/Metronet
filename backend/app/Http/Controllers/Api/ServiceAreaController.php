<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ServiceAreaRequest;
use App\Http\Requests\ServiceAreaUpdateRequest;
use App\Models\ServiceArea;
use Illuminate\Http\JsonResponse;

class ServiceAreaController extends Controller
{
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

    public function viewAreas(): JsonResponse
    {
        try {
            $regions = ServiceArea::where('status', 1)
                ->select('region')
                ->distinct()
                ->pluck('region')
                ->values()
                ->toArray();

            $cities = ServiceArea::where('status', 1)
                ->select('city')
                ->distinct()
                ->pluck('city')
                ->values()
                ->toArray();

            $townships = ServiceArea::where('status', 1)
                ->select('township')
                ->distinct()
                ->pluck('township')
                ->values()
                ->toArray();

           return response()->json([
                'region' => $regions,
                'city' => $cities,
                'township' => $townships
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

    public function update(ServiceAreaUpdateRequest $request, $id): JsonResponse
    {
        try{
            $area = ServiceArea::find($id);

            if (!$area) {
                return response()->json([
                    'message' => 'Not found'
                ]);
            }

            $area->update($request->validated());

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
