<?php

    namespace App\Repositories;

    use App\Models\Customer;
    use App\Contracts\CustomerRepositoryInterface;

    class CustomerRepository implements CustomerRepositoryInterface
    {
        public function create(array $data)
        {
            return Customer::create($data);
        }
    }
?>
