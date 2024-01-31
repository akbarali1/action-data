<?php

namespace Akbarali\ActionData;

use Throwable;

class ActionDataException extends \Exception
{
    /**
     * OperationException constructor.
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public const ERROR_INPUT_VALIDATION     = -423;

}
