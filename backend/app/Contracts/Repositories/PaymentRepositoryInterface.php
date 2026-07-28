<?php

    namespace App\Contracts\Repositories;

    interface PaymentRepositoryInterface
    {
        public function getPayment(int $paymentId);
    }
?>
