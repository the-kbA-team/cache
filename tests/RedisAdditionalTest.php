<?php

namespace Tests\kbATeam\Cache;

use kbATeam\Cache\Redis;

/**
 * Class Tests\kbATeam\Cache\RedisAdditionalTest
 *
 * Additional tests testing the redis class.
 *
 * @category Tests
 * @package  Tests\kbATeam\Cache
 * @license  MIT
 * @link     https://github.com/the-kbA-team/cache.git Repository
 */
class RedisAdditionalTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Data provider for invalid hostnames.
     *
     * @return array
     */
    public static function invalidHostNames()
    {
        return [
            [''],
            ['{abc'],
            ['abc/def'],
            ['invalid..hostname'],
            ['this-sure-looks-valid-but-is-not-because-it-is-far-too-long-for-a-label.com'],
        ];
    }

    /**
     * Data provider for valid hostnames.
     *
     * @return array
     */
    public static function validHostNames()
    {
        return [
            ['127.0.0.1'],
            ['192.168.100.254'],
            ['redis-server'],
            ['redis.example.com'],
        ];
    }

    /**
     * The object of the class being tested, has to be created here.
     * The connection details have to be taken from the environment!
     * @return \kbATeam\Cache\Redis|\Psr\SimpleCache\CacheInterface
     * @throws \kbATeam\Cache\Exceptions\InvalidArgumentException
     */
    public function createRedisInstance()
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
     * @dataProvider invalidHostNames
     */
    public function testHostnameValidationWithInvalidHosts($hostname)
    {
        $this->assertFalse(Redis::isValidHostname($hostname));
    }

    /**
     * @dataProvider validHostNames
     */
    public function testHostnameValidationWithValidHosts($hostname)
    {
        $this->assertTrue(Redis::isValidHostname($hostname));
    }

    public function testGetClient()
    {
        $client = $this->getMockBuilder('\Redis')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('setOption'))
            ->getMock();

        $client
            ->expects($this->once())
            ->method('setOption')
            ->with(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE)
            ->willReturn(true);

        $redis = new Redis($client);
        $this->assertSame($redis->getClient(), $client, "The client object should be the same.");
    }

    public function testInvalidHostnameException()
    {
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "Invalid hostname/IP given: '{abc'"
        );
        Redis::tcp("{abc", 0);
    }

    public function testTcpExceptionOnInvalidDatabaseIndex()
    {
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "database must be integer >= 0, string given!"
        );
        Redis::tcp("127.0.0.1", "X");
    }

    public function testTcpExceptionOnInvalidPassword()
    {
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "password must be a string, boolean given!"
        );
        Redis::tcp("127.0.0.1", 0, false);
    }

    public function testAuthenticationFailure()
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
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "Password authentication failed!"
        );
        \kbATeam\Cache\Redis::tcp(
            $hostname,
            $dbindex,
            "THIS IS AN INVALID PASSWORD!",
            $tcpPort
        );
    }

    public function testInvalidDatabaseIndexException()
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

        if (empty(getenv('REDIS_SERVER_PASSWORD'))) {
            $password = null;
        } else {
            $password = getenv('REDIS_SERVER_PASSWORD');
        }

        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "Invalid database index 999999!"
        );
        \kbATeam\Cache\Redis::tcp(
            $hostname,
            999999,
            $password,
            $tcpPort
        );
    }

    public function testGetMultipleWithWrongParameterType()
    {
        $redis = $this->createRedisInstance();
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentTypeException',
            "keys must be an array, stdClass given!"
        );
        $redis->getMultiple(new \stdClass());
    }

    public function testGetMuptipleWithInvalidArrayStructure()
    {
        $redis = $this->createRedisInstance();
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentTypeException',
            "keys must be an array or an instance of \Traversable, array given!"
        );
        $get = array('key1' => 'value1', 'key2', 'value2');
        $redis->getMultiple($get);
    }

    public function testDeleteMultipleWithWrongParameterType()
    {
        $redis = $this->createRedisInstance();
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentTypeException',
            "keys must be an array, stdClass given!"
        );
        $redis->deleteMultiple(new \stdClass());
    }

    public function testDeleteMuptipleWithInvalidArrayStructure()
    {
        $redis = $this->createRedisInstance();
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentTypeException',
            "keys must be an array or an instance of \Traversable, array given!"
        );
        $del = array('key1' => 'value1', 'key2', 'value2');
        $redis->deleteMultiple($del);
    }
}
