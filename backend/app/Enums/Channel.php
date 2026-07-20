<?php

    declare(strict_types=1);

    namespace App\Enums;

    enum Channel: int
    {
        case EMAIL =1;
        case SMS =2;
        case IN_APP =3;
    }
?>
