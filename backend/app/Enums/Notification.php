<?php

    declare(strict_types=1);

    namespace App\Enum;

    enum SentStatus: int
    {
        case PENDING = 1;
        case SENT = 2;
        case FAILED = 3;
    }
?>
