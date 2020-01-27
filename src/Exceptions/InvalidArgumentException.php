<?php

namespace kbATeam\Cache\Exceptions;

use Exception;

/**
 * Class kbATeam\Cache\Exceptions\InvalidArgumentException
 *
 * Exception interface for invalid cache arguments.
 *
 * When an invalid argument is passed it must throw an exception.
 *
 * @category Exception
 * @package  kbATeam\Cache\Exceptions
 * @license  MIT
 * @link     https://github.com/the-kbA-team/cache.git Repository
 */
class InvalidArgumentException extends Exception implements \Psr\SimpleCache\InvalidArgumentException
{
}
