<?php

namespace App\Service\Exception;

final class DuplicateListNameException extends \RuntimeException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('A list named "%s" already exists.', $name));
    }
}
