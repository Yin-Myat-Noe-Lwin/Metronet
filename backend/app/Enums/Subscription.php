<?php

    declare(strict_types=1);

    namespace App\Enum;

    enum SubscriptionStatus: int{
        case PENDING = 0;
        case ACTIVE = 1;
        case SUSPENED = 2;
        case EXPIRED = 3;
        case CANCELLED = 4;
        case REJECTED = 5;
    }
?>
