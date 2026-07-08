<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerAddressRequest;
use App\Http\Requests\CustomerAddressUpdateRequest;
use App\Models\CustomerAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PDOException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class CustomerAddressController extends Controller
{
    public function viewAddresses(): JsonResponse
    {
        try {

            $customer = Auth::user();

            $addresses = CustomerAddress::where('customer_id', $customer->id)
                                            ->get();

            return response()->json([
                'data' => $addresses
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

    public function store(CustomerAddressRequest $request): JsonResponse
    {
        try{
            $customer = Auth::user();

            Log::info("customer logged in".$customer->id);

            DB::transaction(function () use ($request, $customer) {

                // if  the new address is primary then change the prev address to secondary address
                if ($request->boolean('is_primary')) {
                    CustomerAddress::where('customer_id', $customer->id)
                                    ->where('is_primary', 1)
                                    ->update(['is_primary' => 0]);
                }

                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'address' => $request->address,
                    'township' => $request->township,
                    'city' => $request->city,
                    'region' => $request->region,
                    'address_type' => $request->address_type,
                    'is_primary' => $request->boolean('is_primary')
                ]);
            });

            return response()->json([
                'message' => 'Address added successfully.'
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

    public function update(CustomerAddressUpdateRequest $request, $id): JsonResponse
    {
        try {
            $customer = Auth::user();

            $customer_address = $customer->addresses()->where('id', $id)->first();

            if (!$customer_address) {
                return response()->json([
                    'message' => 'Address not found'
                ]);
            }

            // make sure customer can't change the status again (primary, secondary)
            $data = $request->only([
                    'address',
                    'township',
                    'city',
                    'region',
                    'address_type'
            ]);

            $customer_address->update($data);

            return response()->json([
                'message' => 'Address Updated Successfully',
                'data' => $customer_address
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
        try {
            $customer = Auth::user();

            $address = CustomerAddress::where('id', $id)
                                        ->where('customer_id', $customer->id)
                                        ->first();

            if (!$address) {
                return response()->json([

                    'message' => 'Address not found'
                ]);
            }

            if($address->is_primary == 1) {
                return response()->json([
                    'error' => 'You can\'t delete the primary address.'
                ]);
            }

            $address->delete();

            return response()->json([
                'message' => 'The address deleted Successfully'
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
                'message' => 'Something went wrong'
            ]);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $addresses = CustomerAddress::where('is_primary', 1)->get();

            return response()->json([
                'data' => $addresses
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
