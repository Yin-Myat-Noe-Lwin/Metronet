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

class CpeController extends Controller
{
    public function index(): JsonResponse
    {
        try{
            $cpes = Cache::remember('cpes_list', 60, function () {
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
        try{
            $cpe = Cpe::find($id);

            if (!$cpe) {
                return response()->json([
                    'message' => 'CPE not found'
                ]);
            }

            $cpe->update($request->validated());

            return response()->json([
                'message' => 'CPE updated successfully',
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

    public function destroy($id): JsonResponse
    {
        $cpe = Cpe::where('id', $id)->first();

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
