<?php

    declare(strict_types=1);

    namespace App\Enum;

    enum AddressType: int{
        case HOME = 1;
        case OFFICE = 2;
        case BUSINESS =3;
    }

?>
