<?php

    declare(strict_types=1);

    namespace App\Enums;

    enum EventType: int
    {
        case INVOICE_CREATED = 1;
        case PAYMENT_STATUS = 2;
        case SUBSCRIPTION_APPROVED=3;
        case SERVICE_ACTIVATED = 4;
        case SUBSCRIPTION_CANCELLED = 5;
        case PLAN_UPDATED = 6;
        case PLAN_DELETED = 7;
    }
?>
