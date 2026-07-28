<?php

    namespace App\Repositories;

    use App\Models\Payment;
    use App\Contracts\Repositories\PaymentRepositoryInterface;

    class PaymentRepository implements PaymentRepositoryInterface
    {
        public function getPayment(int $paymentId): Payment
        {
            return Payment::findOrFail($paymentId);
        }
    }
?>
