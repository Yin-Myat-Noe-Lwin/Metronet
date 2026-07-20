<?php

    declare(strict_types=1);

    namespace App\Enum;

    enum SubscriptionStatus: int{
        case PENDING = 1;
        case ACTIVE = 2;
        case SUSPENED = 3;
        case EXPIRED = 4;
        case CANCELLED = 5;
    }
?>
