<?php

namespace App\Services;

use App\Models\Invoice;
use App\Contracts\Repositories\InvoiceRepositoryInterface;

class InvoiceService {

    public function __construct (
        private InvoiceRepositoryInterface $invoiceRepository
    ) {

    }

    public function getInvoiceById(int $id) {

        return $this->invoiceRepository->getInvoiceById($id);
    }

}
