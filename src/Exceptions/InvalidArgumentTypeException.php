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
        Throwable $previous = null
    ) {
        //compile message from parameters
        $message = sprintf(
            "%s must be %s, %s given!",
            $argName,
            $expected,
            $this->getArgType($given)
        );
        parent::__construct($message, 0, $previous);
    }

    /**
     * Get the type of the given argument.
     * @param mixed $given Referenced argument to get the type of.
     * @return string The determined type of the given argument.
     */
    public function getArgType(&$given)
    {
        if (is_object($given)) {
            return get_class($given);
        }
        return gettype($given);
    }
}
