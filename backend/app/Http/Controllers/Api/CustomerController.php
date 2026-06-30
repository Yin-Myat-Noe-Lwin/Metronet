<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Mail\VerifyRegisterMail;
use App\Mail\VerifyUpdateMail;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PDOException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;

class CustomerController extends Controller
{
    public function profile(): JsonResponse
    {
        try{
            $customer = Auth::user();

            $primary_address = $customer->addresses->where('is_primary', 1)
                                                    ->first();

            if($primary_address) {
                $full_primary_address = implode(', ', array_filter([
                                        $primary_address->address,
                                        $primary_address->township,
                                        $primary_address->city,
                                        $primary_address->region
                        ]));
                Log::info('customer primary address'.$full_primary_address);
            }

            $secondary_address = $customer->addresses->where('is_primary', 0)
                                                    ->first();

            if($secondary_address) {
                $full_secondary_address = implode(', ', array_filter([
                                        $secondary_address->address,
                                        $secondary_address->township,
                                        $secondary_address->city,
                                        $secondary_address->region
                        ]));
                Log::info('customer secondary address'.$full_secondary_address);
            }

            $subscriptions = $customer->subscriptions;

            return response()->json([
                'message' => 'Profile',
                'name' => $customer->name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_num,
                'subscribed_plans' => $subscriptions->isEmpty() ? [] : $subscriptions,
                'primary_address' => $full_primary_address?? 'No Primary Address',
                'secondary_address' => $full_secondary_address?? 'No Secondary Address'
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' => 'Database Connection Error'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Database operation failed.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Record not found.'
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  'Authenication failed.'
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    public function updateProfile(ProfileUpdateRequest $request): JsonResponse
    {
        try{
            $customer = Auth::user();

            if ($request->filled('email') && $customer->email !== $request->email) {

                Log::info('user pending mail'.$request->email);

                $verificationToken = Str::random(64);

                $verificationUrl = 'http://localhost:8080/api/update-email?token='
                                    .$verificationToken;

                Mail::to($request->email)
                ->send(new VerifyUpdateMail($verificationUrl));

                $customer->update([
                    'pending_email' => $request->email,
                    'verification_token' => $verificationToken,
                    'verification_token_expires_at' => now()->addHours(6)
                ]);
            }

            $customer->update([
                'name' => $request->name,
                'phone_num' => $request->phone_num
            ]);

            return response()->json([
                'message' => 'Profile was updated successfully. To verify new mail, check your mail.'
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' => 'Database Connection Error'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Database operation failed.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Record not found.'
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  'Authenication failed.'
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        try{
            $verification_token = $request->token;

            $customer = Customer::where('verification_token', $verification_token)->first();

            if(!$customer) {
                return response()->json([
                    'error' => 'invalid token'
                ]);
            }

            // if time passes over 6hrs
            if($customer->verification_token_expires_at < now()) {
                Log::info("token expired");
                return response()->json([
                    'error' => 'verification link expired'
                ]);
            }

            Log::info('new updated email'.$customer->pending_email);

            $customer->update([
                'email' => $customer->pending_email,
                'pending_email' => null,
                'verification_token' => null,
                'verification_token_expires_at' => null,
                'email_verified_at' => now()
            ]);

            return response()->json([
                'message' => 'Email was updated successfully.'
            ]);
        }
        catch (PDOException $e) {
            return response()->json([
                'message' => 'Database Connection Error'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Database operation failed.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Record not found.'
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  'Authenication failed.'
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.'
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
            $page = request('page', 1);

            $customers = Cache::remember("customers_page_$page", 60, function () {
                            return Customer::orderBy('created_at', 'desc')
                                ->paginate(20);
                        });

            return response()->json([
                'data' => $customers
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' => 'Database Connection Error'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Database operation failed.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Record not found.'
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  'Authenication failed.'
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $customer = Customer::with([
                'addresses',
                'subscriptions.plan'
            ])
            ->find($id);

            if (!$customer) {
                return response()->json([
                    'message' => 'Customer not found'
                ]);
            }

            return response()->json([
                'data' => $customer
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' => 'Database Connection Error'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Database operation failed.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Record not found.'
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  'Authenication failed.'
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.'
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
            $customer = Customer::where('id', $id)->first();

            if (!$customer) {
                return response()->json([
                    'message' => 'Customer not found'
                ]);
            }

            $customer->update([
                'status' => 0 // inactive
            ]);

            return response()->json([
                'message' => 'Customer deactivated successfully'
            ]);
        } catch (PDOException $e) {
            return response()->json([
                'message' => 'Database Connection Error'
            ]);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Database operation failed.'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Record not found.'
            ]);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' =>  'Authenication failed.'
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Something went wrong.'
            ]);
        }
    }
}
