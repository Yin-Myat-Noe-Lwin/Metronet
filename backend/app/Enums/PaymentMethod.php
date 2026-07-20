<?php

    declare(strict_types=1);

    namespace App\Enum;

    enum PaymentMethod: int{
        case emoji = 1;

        case image_url = 2;

        case uploaded_file = 3;
    }
?>
