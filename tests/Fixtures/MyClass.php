<?php
declare(strict_types=1);

namespace Tests\Fixtures;

class MyClass
{
    public function concatenateStrings(string $str1, string $str2): string
    {
        return $str1.$str2;
    }
}