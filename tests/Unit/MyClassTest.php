<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\MyClass;

class MyClassTest extends TestCase
{
    private MyClass $myClass;

    public function setUp(): void
    {
        $this->myClass = new MyClass();
    }

    public function tearDown(): void
    {
        // Clean up any resources used during testing
    }

    /**
     * @dataProvider concatenationDataProvider
     */
    public function testConcatenateStrings($str1, $str2, $expected): void
    {
        $result = $this->myClass->concatenateStrings($str1, $str2);

        $this->assertEquals($expected, $result);
    }

    public static function concatenationDataProvider(): array
    {
        return [
            ['hello', 'world', 'helloworld'],
            ['foo', 'bar', 'foobar1'],
            ['', '', ''],
        ];
    }
}