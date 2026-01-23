<?php

namespace StuMason\Kick\Exceptions;

use Exception;

class CommandNotAllowedException extends Exception
{
    public function __construct(string $command)
    {
        parent::__construct("Command not allowed: {$command}");
    }
}
