<?php

    namespace App\Contracts\Repositories;

    interface InvoiceRepositoryInterface
    {
      public function getInvoiceById(int $id);
    }
?>
