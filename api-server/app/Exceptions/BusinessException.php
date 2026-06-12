<?php


namespace App\Exceptions;

use Exception;
use Throwable;

class BusinessException extends Exception
{
    public function __construct($code = 0, $message = "", Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
