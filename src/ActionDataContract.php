<?php

namespace Akbarali\ActionData;

use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

interface ActionDataContract
{
    public static function createFromRequest(Request $request);

    public static function createFromArray(array $parameters = []);

    /**
     * @param bool $silent
     * @return bool
     * @throws ValidationException
     */
    public function validate(bool $silent = true): bool;

    public function getValidationErrors(): ?MessageBag;

}
