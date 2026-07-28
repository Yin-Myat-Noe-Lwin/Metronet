<?php

    namespace App\Repositories;
    use App\Contracts\Repositories\InvoiceRepositoryInterface;

    use App\Models\Invoice;

    class InvoiceRepository implements InvoiceRepositoryInterface
    {
        public function getInvoiceById(int $id)
        {
            return Invoice::find($id);
        }
    }
?>
