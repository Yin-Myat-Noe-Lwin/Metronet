<?php

    declare(strict_types=1);

    namespace App\Enum;

    enum Invoice: int {
        case PENDING = 0;
        case PAID = 1;
        case OVERDUE = 2;
        case CANCELLED =3;
    }
?>
