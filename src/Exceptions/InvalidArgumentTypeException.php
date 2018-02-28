<?php

namespace kbATeam\Cache\Exceptions;

use Throwable;

/**
 * Class kbATeam\Cache\Exceptions\InvalidParameterException
 *
 * Exception class for invalid function parameters.
 *
 * @category Exception
 * @package  kbATeam\Cache\Exceptions
 * @license  MIT
 * @link     https://github.com/the-kbA-team/cache.git Repository
 */
class InvalidArgumentTypeException extends InvalidArgumentException
{
    /**
     * InvalidParameterException constructor.
     * @param string          $argName  The name of the invalid argument.
     * @param string          $expected A description of the expected value or type.
     * @param mixed           $given    The actual object/variable given as argument.
     * @param \Throwable|null $previous Any previously raised error.
     */
    public function __construct(
        $argName,
        $expected,
        &$given,
        \Throwable $previous = null
    ) {
        //determine type of given argument
        if (is_object($given)) {
            $type = get_class($given);
        } else {
            $type = gettype($given);
        }
        //compile message from parameters
        $message = sprintf(
            "%s must be %s, %s given!",
            $argName,
            $expected,
            $type
        );
        parent::__construct($message, 0, $previous);
    }
}
