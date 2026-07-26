<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentService
{
    public function getPayment(int $paymentId): Payment
    {
        return Payment::findOrFail($paymentId);
    }
}
