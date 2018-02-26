<?php

namespace Tests\kbATeam\Cache;

use kbATeam\Cache\Redis;

/**
 * Class Tests\kbATeam\Cache\RedisTest
 *
 * Redis simple cache adapter test.
 *
 * @category Tests
 * @package  Tests\kbATeam\Cache
 * @license  MIT
 * @link     https://github.com/the-kbA-team/cache.git Repository
 */
class RedisTest extends \PHPUnit_Framework_TestCase
{

    /**
     * test successful object creation
     */
    public function testConstructor()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $redis = new Redis($client);
        $this->assertInstanceOf('\kbATeam\Cache\Redis', $redis);
    }

    /**
     * basic successful get() test
     */
    public function testGet()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('get'))
            ->getMock();

        $client->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->will($this->returnValue(serialize('test value')));

        $redis = new Redis($client);
        $this->assertEquals('test value', $redis->get('test key'));
    }

    /**
     * basic successful get() test
     */
    public function testGetWithEmptyKey()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('get'))
            ->getMock();

        $redis = new Redis($client);
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "Invalid Argument: Given key is empty!"
        );
        $redis->get('');
    }

    /**
     * basic successful get() test
     */
    public function testGetWithInvalidKey()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('get'))
            ->getMock();

        $redis = new Redis($client);
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "Invalid Argument: Given key is not a string!"
        );
        $redis->get(true);
    }

    /**
     * test get with default value
     */
    public function testGetWithDefaultValue()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('get'))
            ->getMock();

        $client->expects($this->once())
            ->method('get')
            ->with('test__key')
            ->will($this->returnValue(""));

        $redis = new Redis($client);
        $this->assertEquals(
            'another value',
            $redis->get('test ,key', 'another value')
        );
    }

    /**
     * test setting a value
     */
    public function testSet()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('setnx'))
            ->getMock();

        $client->expects($this->once())
            ->method('setnx')
            ->with('test_key', serialize('test value'))
            ->will($this->returnValue("OK"));

        $redis = new Redis($client);
        $this->assertTrue($redis->set('test:key', 'test value'));
    }

    /**
     * test setting a value with a ttl given as integer
     */
    public function testSetWithIntTtl()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('setex'))
            ->getMock();

        $client->expects($this->once())
            ->method('setex')
            ->with('test_key', 101, serialize('test value'))
            ->will($this->returnValue("OK"));

        $redis = new Redis($client);
        $this->assertTrue($redis->set('test*key', 'test value', 101));
    }

    /**
     * test setting a value with ttl given as time difference
     */
    public function testSetWithDateIntervalTtl()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('setex'))
            ->getMock();

        $client->expects($this->once())
            ->method('setex')
            ->with('test_key', 202, serialize('test value'))
            ->will($this->returnValue("OK"));

        $redis = new Redis($client);
        $ttl = new \DateInterval("PT202S");
        $this->assertTrue($redis->set('test*key', 'test value', $ttl));
    }

    /**
     * Test the exception thrown in case ttl is not given as integer or DateInterval.
     */
    public function testSetWithInvalidTtl()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('setex'))
            ->getMock();
        $redis = new Redis($client);
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            'Invalid Argument: Time-to-live must either be an integer, a DateInterval or null, "string" given'
        );
        $redis->set("test key", "test value", "303");
    }

    /**
     * Test successfully deleting a key.
     */
    public function testSuccessfulDelete()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('del'))
            ->getMock();

        $client->expects($this->once())
            ->method('del')
            ->with('test_key')
            ->will($this->returnValue(1));

        $redis = new Redis($client);
        $this->assertTrue($redis->delete('test+key'));
    }

    /**
     * Test unsuccessfully deleting a key.
     */
    public function testUnsuccessfulDelete()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('del'))
            ->getMock();

        $client->expects($this->once())
            ->method('del')
            ->with('test_key')
            ->will($this->returnValue(100));

        $redis = new Redis($client);
        $this->assertFalse($redis->delete('test key'));
    }

    /**
     * Test clear function (which will always return true).
     */
    public function testClear()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('flushdb'))
            ->getMock();

        $client->expects($this->once())
            ->method('flushdb')
            ->will($this->returnValue(1));

        $redis = new Redis($client);
        $this->assertTrue($redis->clear());
    }

    /**
     * Test a cache hit.
     */
    public function testHas()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('exists'))
            ->getMock();

        $client->expects($this->once())
            ->method('exists')
            ->with('test_key')
            ->will($this->returnValue(1));

        $redis = new Redis($client);
        $this->assertTrue($redis->has('test key'));
    }

    /**
     * Test a cache miss.
     */
    public function testHasMiss()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('exists'))
            ->getMock();

        $client->expects($this->once())
            ->method('exists')
            ->with('test_key')
            ->will($this->returnValue(0));

        $redis = new Redis($client);
        $this->assertFalse($redis->has('test key'));
    }

    /**
     * Test getting multiple results at once.
     */
    public function testGetMultiple()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('get'))
            ->getMock();

        $client->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValue(serialize("success")));

        $redis = new Redis($client);
        $get_arr = ['key1', 'key2'];
        $expect = ['key1' => 'success', 'key2' => 'success'];
        $this->assertEquals($expect, $redis->getMultiple($get_arr));
    }

    /**
     * Test exception thrown in case the parameter for getMultiple() is not an array.
     */
    public function testGetMultipleInvalidParameter()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('get'))
            ->getMock();

        $redis = new Redis($client);
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "Invalid Argument: Keys must be an array!"
        );
        $redis->getMultiple('test keys');
    }

    /**
     * Test setting multiple values at once.
     */
    public function testSetMultiple()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('setnx'))
            ->getMock();

        $client
            ->expects($this->exactly(2))
            ->method('setnx')
            ->willReturn('OK');

        $redis = new Redis($client);
        $this->assertTrue(
            $redis->setMultiple(array('key1' => 'value 1', 'key 2' => 'value two'))
        );
    }

    /**
     * Test setting multiple values at once.
     */
    public function testCacheExceptionOnSetMultiple()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('setnx'))
            ->getMock();

        $client
            ->method('setnx')
            ->willReturn('ERR');

        $redis = new Redis($client);
        $this->assertFalse($redis->setMultiple(array('key1' => 'value 1', 'key 2' => 'value two')));
    }

    /**
     * Test for the exception being thrown when setMultiple does not get an array as parameter.
     */
    public function testParameterExceptionOnSetMultiple()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('setnx'))
            ->getMock();

        $redis = new Redis($client);
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "Invalid Argument: Values must be an associative array of key=>value!"
        );
        $redis->setMultiple('test value');
    }

    /**
     * Test setting multiple values at once.
     */
    public function testDeleteMultiple()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('del'))
            ->getMock();

        $client
            ->expects($this->exactly(2))
            ->method('del')
            ->willReturn(1);

        $redis = new Redis($client);
        $this->assertTrue(
            $redis->deleteMultiple(array('key1','key 2'))
        );
    }

    /**
     * Test setting multiple values at once.
     */
    public function testCacheExceptionOnDeleteMultiple()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('del'))
            ->getMock();

        $client
            ->method('del')
            ->willReturn(0);

        $redis = new Redis($client);
        $this->assertFalse(
            $redis->deleteMultiple(array('key1','key 2'))
        );
    }

    /**
     * Test setting multiple values at once.
     */
    public function testParameterExceptionOnDeleteMultiple()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->setMethods(array('del'))
            ->getMock();

        $redis = new Redis($client);
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "Invalid Argument: Keys must be an array!"
        );
        $redis->deleteMultiple('key1 key 2');
    }

    /**
     * Test successful connection to a redis server.
     */
    public function testTcpConnectClient()
    {
        $redis = Redis::tcp('127.0.0.1', 1, "swordfish");
        $connection = $redis->getClient()->getConnection();
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);
        $parameters = $connection->getParameters();
        $this->assertEquals('tcp', $parameters->scheme);
        $this->assertEquals('127.0.0.1', $parameters->host);
        $this->assertEquals(6379, $parameters->port);
        $this->assertEquals(1, $parameters->database);
        $this->assertEquals('swordfish', $parameters->password);
    }

    /**
     * Test invalid hostname exception.
     */
    public function testTcpConnectWithInvalidHostname()
    {
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "Invalid Argument: Invalid hostname/IP given: '[[['"
        );
        Redis::tcp('[[[', 1, "swordfish");
    }

    /**
     * Test invalid database id.
     */
    public function testTcpConnectWithInvalidDatabase()
    {
        $this->setExpectedException(
            '\kbATeam\Cache\Exceptions\InvalidArgumentException',
            "Invalid Argument: Database has to be an integer!"
        );
        Redis::tcp('redis-server', 'abc', "swordfish");
    }

    /**
     * Test the cluster connection.
     */
    public function testClusterConnect()
    {
        $redis = Redis::cluster(['host1', 'host2'], 1, "swordfish");
        $connection = $redis->getClient()->getConnection();
        $this->assertInstanceOf('Predis\Connection\Aggregate\ClusterInterface', $connection);
        $this->assertEquals(2, $connection->count());
    }
}
