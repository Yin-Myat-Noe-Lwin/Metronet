<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceService {

    public function getInvoiceById(int $id) {

        return Invoice::find($id);
    }

}
