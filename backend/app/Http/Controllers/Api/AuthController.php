<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Models\Customer;
use App\Contracts\Services\AuthServiceInterface;
use App\Http\Controllers\Controller;
use App\Mail\VerifyRegisterMail;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PDOException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Notifications\ResetPassword;

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

        Log::info("Registration successsful");

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

            // remember me flag
            $remember = $request->boolean('remember', false);

            if (!$customer) {
                return response()->json([
                    'message' => 'Invalid Email or Password'
                ], 401);
            }

            // If remember is true, set token TTL to 7 days
            if ($remember) {
                auth()->factory()->setTTL(60 * 24 * 7); // 7 days in minutes
            } else {
                auth()->factory()->setTTL(60); // 1 hour default
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
                'user' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone_num' => $customer->phone_num,
                    'role' => $customer->role,
                    'status' => $customer->status,
                ]
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

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return 'http://localhost:5173/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });
        // send password reset mail to customer mail
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent to your email.'
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)]
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = bcrypt($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password has been reset successfully.'
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)]
        ]);
    }
}
