<?php

    namespace App\Services;

    use App\Contracts\AuthServiceInterface;
    use App\Contracts\CustomerRepositoryInterface;

    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Mail;
    use App\Mail\VerifyRegisterMail;

    class AuthService implements AuthServiceInterface
    {
        public function __construct(
            private CustomerRepositoryInterface $customerRepository
        )
        {

        }

        public function register(array $data)
        {
            // create token for verification
            $verificationToken = Str::random(64);

            // check auto verify true or false
            $isAutoVerify = config(
                'metronet.auto_verify'
            );

            Log::info('Auto Verify Status:'. $isAutoVerify);

            $customer = $this->customerRepository->create([
                'name' => $dat['name'],
                'phone_num' => $data['phone_num'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => $isAutoVerify ? 1 : 0,
                'verification_token' => $verificationToken,
                'verification_token_expires_at' => now()->addHours(6)
            ]);

            // if no auto verify on, have to verify mail
            if (!$isAutoVerify) {
                // verification url for registered customer
                $verificationUrl = 'http://localhost/verify-email?token='
                                    . $verificationToken;

                Log::info('verification url: ' . $verificationUrl);

                // send mail to customer
                Mail::to($customer->email)
                    ->send(new verifyRegisterMail($verificationUrl));

                $message = 'Your account was created successfully. Please verify your email.';
            } else {
                $message = 'Your account was created and verified successfully.';
            }

            return response()->json([
                'message' => $message,
                'customer' => $customer,
                'auto_verified' => $isAutoVerify
            ]);

        }
    }

?>
