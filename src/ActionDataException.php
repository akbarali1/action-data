<?php

namespace Akbarali\ActionData;

use Throwable;

class ActionDataException extends \Exception
{
	
	/**
	 * OperationException constructor.
	 *
	 * @param  string          $message
	 * @param  int             $code
	 * @param  Throwable|null  $previous
	 */
	public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
	
	public const        ERROR_INPUT_VALIDATION                = -423;
	public const        ERROR_NOT_NULLABLE                    = -623;
	public const        ERROR_INTERFACE_NOT_IMPLEMENTED       = -823;
	public const        ERROR_INTERFACE_NOT_IMPLEMENTED_TRAIT = -824;
	
}
