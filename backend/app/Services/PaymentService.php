<?php

namespace App\Services;

use App\Models\Payment;
use App\Contracts\Repositories\PaymentRepositoryInterface;

class PaymentService
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    )
    {
    }

    public function getPayment(int $paymentId): Payment
    {
        return $this->paymentRepository
                    ->getPayment($paymentId);
    }
}
