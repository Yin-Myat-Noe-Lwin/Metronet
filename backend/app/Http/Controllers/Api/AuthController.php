<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

use App\Http\Requests\RegisterRequest;

use App\Contracts\AuthServiceInterface;

use App\Http\Controllers\Controller;

class AuthController extends Controller
{

    public function __construct(
        private AuthServiceInterface $authService
    )
    {

    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService
                        ->register($request->validated());

        Log::info('Registered Customer:', $result);

        return response()->json([
            'message' => $result['message'],
            'customer' => $result['customer'],
            'auto_verified' => $result['auto_verified']
        ]);
    }

    public function verifyEmail(Request $request):JsonResponse
    {
        try {
            Log::info('token data'.$request->token);

            $verification_token = $request->token;

            $customer = Customer::where('verification_token', $verification_token)->first();

            if(!$customer) {
                return response()->json([
                    'error' => 'ivalid token'
                ]);
            }

            // if time passes over 6 hrs
            if($customer->verification_token_expires_at < now()) {
                Log::info("token expired");
                return response()->json([
                    'error' => 'verification link expired'
                ]);
            }

            Log::info('the customer'.$customer);

            $customer->update([
                'status' => 1,
                'email_verified_at' => now(),
                'verification_token' => null,
                'verification_token_expires_at' => null
            ]);

            return response()->json([
                'message' => 'Your email has verified successfully.'
            ]);
        }  catch (PDOException $e) {
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

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            $customer = Customer::where('email', $credentials['email'])->first();

            if (!$customer) {
                return response()->json([
                    'message' => 'Invalid Email or Password'
                ], 401);
            }

            if ($customer && $customer->status === 0) {
                return response()->json([
                    'message' => 'Please verify your email first'
                ], 403);
            }

            $token = Auth::attempt($credentials);

            if (!$token) {
                return response()->json([
                    'message' => 'Invalid credentials'
                ], 401);
            }

            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'role' => $customer->role,
            ]);

        } catch (PDOException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Database operation failed.'
            ], 500);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Record not found.'
            ], 404);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => 'Authentication failed.'
            ], 401);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => 'You are not authorized to perform this action.'
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            Auth::logout();

            return response()->json([
                'message' => 'Logged out successfully'
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
