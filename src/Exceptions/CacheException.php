<?php

namespace kbATeam\Cache\Exceptions;

use Exception;

/**
 * Class kbATeam\Cache\Exceptions\CacheException
 *
 * General cache exception used for all types of exceptions thrown by the implementing library.
 *
 * @category Exception
 * @package  kbATeam\Cache\Exceptions
 * @license  MIT
 * @link     https://github.com/the-kbA-team/cache.git Repository
 */
class CacheException extends Exception implements \Psr\SimpleCache\CacheException
{

}
