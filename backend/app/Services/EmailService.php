<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmailService
{

    public function send(
        Customer $customer,
        Mailable $mail
    ): void {

        try {

            if (!$customer->email) {
                Log::warning('Customer has no email', [
                    'customer_id' => $customer->id
                ]);

                return;
            }

            Mail::to($customer->email)
                ->send($mail);

            Log::info('Email sent successfully', [
                'customer_id' => $customer->id,
                'email' => $customer->email
            ]);

        } catch (Throwable $e) {

            Log::error('Email sending failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
