<?php

namespace Tests\kbATeam\Cache;

/**
 * Class Tests\kbATeam\Cache\RedisIntegrationTest
 *
 * PSR-16 simple cache integration test.
 *
 * @category Tests
 * @package  Tests\kbATeam\Cache
 * @license  MIT
 * @link     https://github.com/the-kbA-team/cache.git Repository
 */
class RedisIntegrationTest extends \Cache\IntegrationTests\SimpleCacheTest
{
    /**
     * The object of the class being tested, has to be created here.
     * The connection details have to be taken from the environment!
     * @return \kbATeam\Cache\Redis|\Psr\SimpleCache\CacheInterface
     * @throws \kbATeam\Cache\Exceptions\InvalidArgumentException
     */
    public function createSimpleCache()
    {
        if (empty(getenv('REDIS_SERVER_HOST'))) {
            $hostname = '127.0.0.1';
        } else {
            $hostname = getenv('REDIS_SERVER_HOST');
        }

        if (empty(getenv('REDIS_SERVER_PORT'))) {
            $tcpPort = 6379;
        } else {
            $tcpPort = getenv('REDIS_SERVER_PORT');
        }

        if (empty(getenv('REDIS_SERVER_DBINDEX'))) {
            $dbindex = 0;
        } else {
            $dbindex = getenv('REDIS_SERVER_DBINDEX');
        }

        if (empty(getenv('REDIS_SERVER_PASSWORD'))) {
            $password = null;
        } else {
            $password = getenv('REDIS_SERVER_PASSWORD');
        }

        return \kbATeam\Cache\Redis::tcp(
            $hostname,
            $dbindex,
            $password,
            $tcpPort
        );
    }

    /**
     * Data provider for invalid keys.
     *
     * Remove the pos. #4 of the parent class containing (int)2 from the list
     * of invalid keys because Redis::redisValidateKey() casts integers to
     * strings.
     *
     * This is supposed to be a temporary solution.
     * See https://github.com/php-cache/integration-tests/issues/91
     *
     * @return array parent array except for pos. #4 containing valid key (int)2.
     */
    public static function invalidKeys()
    {
        $return = parent::invalidKeys();
        unset($return[4]); //As keys, strings are always casted to ints so they should be accepted
        return $return;
    }
}
