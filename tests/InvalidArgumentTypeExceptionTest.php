<?php

namespace Tests\kbATeam\Cache;

use kbATeam\Cache\Exceptions\InvalidArgumentTypeException;

/**
 * Class Tests\kbATeam\Cache\InvalidArgumentTypeExceptionTest
 *
 * Test the invalid argument type exception.
 *
 * @category Tests
 * @package  Tests\kbATeam\Cache
 * @license  MIT
 * @link     https://github.com/the-kbA-team/cache.git Repository
 */
class InvalidArgumentTypeExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWithSimpleType()
    {
        $bool = true;
        $e = new InvalidArgumentTypeException("Fun", "expected", $bool);
        $this->assertInstanceOf('\Exception', $e);
        $this->assertInstanceOf('\Psr\SimpleCache\InvalidArgumentException', $e);
        $this->assertInstanceOf('\kbATeam\Cache\Exceptions\InvalidArgumentException', $e);
        $this->assertInstanceOf('\kbATeam\Cache\Exceptions\InvalidArgumentTypeException', $e);
        $this->assertEquals("Fun must be expected, boolean given!", $e->getMessage());
    }

    public function testConstructorWithComplexType()
    {
        $arr = array();
        $e = new InvalidArgumentTypeException("Programming", "hard", $arr);
        $this->assertEquals("Programming must be hard, array given!", $e->getMessage());
    }

    public function testConstructorWithObject()
    {
        $obj = new \stdClass();
        $e = new InvalidArgumentTypeException("Objects", "avoided", $obj);
        $this->assertEquals("Objects must be avoided, stdClass given!", $e->getMessage());
    }
}
