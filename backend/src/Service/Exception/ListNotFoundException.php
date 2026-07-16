<?php

namespace App\Service\Exception;

final class ListNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Favorite list not found.');
    }
}
