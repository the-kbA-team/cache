<?php

namespace kbATeam\Cache;

use kbATeam\Cache\Exceptions\InvalidArgumentException;
use kbATeam\Cache\Exceptions\InvalidArgumentTypeException;

/**
 * Class kbATeam\Cache\Redis
 *
 * Simple cache using Redis implementing PSR-16.
 *
 * @category Library
 * @package  kbATeam\Cache
 * @license  MIT
 * @link     https://github.com/the-kbA-team/cache.git Repository
 */
class Redis implements \Psr\SimpleCache\CacheInterface
{

    /**
     * @var \Redis
     */
    protected $client;

    /**
     * Redis simple cache constructor.
     * @param \Redis $client The redis client to connect to handle the redis connection.
     */
    public function __construct(\Redis $client)
    {
        $client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_NONE);
        $this->client = $client;
    }

    /**
     * Get the redis client application.
     * @return \Redis
     */
    public function getClient(): \Redis
    {
        return $this->client;
    }

    /**
     * Return a redis cache object, that will connect via tcp to a redis server.
     * @param string      $host     Either hostname or IP address of the redis server.
     * @param int         $database The database ID to use on the redis server.
     * @param string|null $password Optional password to access the redis server. Default: null
     * @param int         $port     Optional TCP port of the server. Default: 6379
     * @return \kbATeam\Cache\Redis An instance of this class connecting to the given server.
     * @throws \kbATeam\Cache\Exceptions\InvalidArgumentException In case any of the parameters is invalid.
     */
    public static function tcp($host, $database, $password = null, $port = 6379): Redis
    {
        //validate hostname/IP (throws exception in case it's not valid)
        if (!static::isValidHost($host)) {
            throw new InvalidArgumentException(sprintf("Invalid hostname/IP given: '%s'", $host));
        }
        //validate database id
        if (!is_int($database) || 0 > $database) {
            throw new InvalidArgumentTypeException('database', 'integer >= 0', $database);
        }
        //validate password
        if ($password !== null && !is_string($password)) {
            throw new InvalidArgumentTypeException('password', 'a string', $password);
        }
        $client = new \Redis();
        $client->pconnect($host, $port);
        if ($password !== null && !$client->auth($password)) {
            throw new InvalidArgumentException('Password authentication failed!');
        }
        if (!$client->select($database)) {
            throw new InvalidArgumentException(sprintf('Invalid database index %u!', $database));
        }
        return new self($client);
    }

    /**
     * Validates the given hostname and throws an exception in case it's not.
     * @param string $host The hostname to validate.
     * @return boolean Is the given hostname valid?
     */
    public static function isValidHost($host): bool
    {
        return (
            (
                //valid chars check
                preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $host)
                //overall length check
                && preg_match('/^.{1,253}$/', $host)
                //length of each label
                && preg_match("/^[^.]{1,63}(\.[^.]{1,63})*$/", $host)
            )
            || filter_var($host, FILTER_VALIDATE_IP)
        );
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of
     *               cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function get($key, $default = null)
    {
        $keyNormalized = $this->redisValidateKey($key);
        $valueSerialized = $this->client->get($keyNormalized);
        if (empty($valueSerialized)) {
            $result = $default;
        } else {
            /** @noinspection UnserializeExploitsInspection */
            $result = unserialize($valueSerialized);
        }
        return $result;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional
     * expiration TTL time.
     *
     * @param string                $key   The key of the item to store.
     * @param mixed                 $value The value of the item to store, must be
     *                                     serializable.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item. If
     *                                     no value is sent and the driver supports
     *                                     TTL then the library may set a default
     *                                     value for it or let the driver take care
     *                                     of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $ttl = null)
    {
        $keyNormalized = $this->redisValidateKey($key);
        $ttlNormalized = $this->redisNormalizeTtl($ttl);
        if ($ttlNormalized === null) {
            //no TTL
            $result = $this->client->set($keyNormalized, serialize($value));
        } elseif (0 === $ttlNormalized) {
            //ttl <= 0 means: delete!
            $result = $this->client->del(array($keyNormalized));
        } else {
            //set ttl
            $result = $this->client->setex($keyNormalized, $ttlNormalized, serialize($value));
        }
        return $result;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an
     *              error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key)
    {
        $keyNormalized = $this->redisValidateKey($key);
        $this->client->del(array($keyNormalized));
        return true;
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        return $this->client->flushdb(); //never fails
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single
     *                          operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist
     *                  or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null)
    {
        if (!static::isValidKeysArray($keys)) {
            throw new InvalidArgumentTypeException('keys', 'an array or an instance of \Traversable', $keys);
        }

        if ($keys instanceof \Traversable) {
            return $this->getMultipleFromTraversable($keys, $default);
        }

        return $this->getMultipleFromArray($keys, $default);
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param array $keys    A list of keys that can obtained in a single
     *                          operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist
     *                  or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    private function getMultipleFromArray($keys, $default)
    {
        $result = array();
        $keysNormalized = $this->redisNormalizeArrayValuesLikeKeys($keys);
        foreach ($this->client->mget($keysNormalized) as $pos => $valueSerialized) {
            if (empty($valueSerialized)) {
                $value = $default;
            } else {
                /** @noinspection UnserializeExploitsInspection */
                $value = unserialize($valueSerialized);
            }
            $result[$keys[$pos]] = $value;
        }
        return $result;
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys    A list of keys that can obtained in a single
     *                          operation.
     * @param mixed    $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist
     *                  or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    private function getMultipleFromTraversable($keys, $default)
    {
        $result = array();
        foreach ($keys as $key) {
            $keyNormalized = $this->redisValidateKey($key);
            $result[$keyNormalized] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable              $values A list of key => value pairs for a
     *                                      multiple-set operation.
     * @param null|int|\DateInterval $ttl    Optional. The TTL value of this item. If
     *                                      no value is sent and the driver supports
     *                                      TTL then the library may set a default
     *                                      value for it or let the driver take care
     *                                      of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null)
    {
        if (!is_array($values) && !$values instanceof \Traversable) {
            throw new InvalidArgumentTypeException('values', 'an array or an instance of \Traversable', $values);
        }
        $ttlNormalized = $this->redisNormalizeTtl($ttl);
        if ($ttlNormalized === null) {
            //without ttl use redis mset() but normalize keys before
            $result = $this->client->mset(
                $this->redisNormalizeArrayKeysSerializeValue($values)
            );
        } elseif (0 === $ttlNormalized) {
            //ttl <= 0 means delete the normalized keys from the array
            $result = $this->client->del(
                $this->redisNormalizeArrayValuesLikeKeys(array_keys($values))
            );
        } else {
            $result = true;
            foreach ($values as $key => $value) {
                $keyNormalized = $this->redisValidateKey($key);
                if (!$this->client->setex($keyNormalized, $ttlNormalized, serialize($value))) {
                    // @codeCoverageIgnoreStart
                    $result = false;
                    break;
                    // @codeCoverageIgnoreEnd
                }
            }
        }
        return $result;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was
     *              an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        if (!static::isValidKeysArray($keys)) {
            throw new InvalidArgumentTypeException('keys', 'an array or an instance of \Traversable', $keys);
        }
        $this->client->del(
            $this->redisNormalizeArrayValuesLikeKeys($keys)
        );
        return true;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type
     * purposes and not to be used within your live applications operations for
     * get/set, as this method is subject to a race condition where your has() will
     * return true and immediately after, another script can remove it making the
     * state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function has($key)
    {
        $result = $this->client->exists(
            $this->redisValidateKey($key)
        );
        return $result > 0;
    }

    /**
     * Validate and return a key for redis.
     * @param $str
     * @return mixed
     * @throws \kbATeam\Cache\Exceptions\InvalidArgumentException
     */
    private function redisValidateKey($str)
    {
        /**
         * In case of ['0' => 'value0'] the string '0' is interpreted as
         * integer 0, which in turn would lead to a fail of
         * SimpleCacheTest::testSetMultipleWithIntegerArrayKey() line 225.
         *
         * This cast from int to string is supposed to be a temporary solution.
         * See: https://github.com/php-cache/integration-tests/issues/91
         */
        if (is_int($str)) {
            $str = (string)$str;
        }
        if (!is_string($str)) {
            throw new InvalidArgumentTypeException('key', 'a string', $str);
        }
        if (!preg_match('~^[a-zA-Z0-9_.]+$~', $str, $match)) {
            throw new InvalidArgumentException(
                'Key must consist of alphanumeric values, underlines and dots!'
            );
        }
        return $match[0];
    }

    /**
     * Normalize the keys of an array.
     * ATTENTION: This function receives a reference and returns a reference!
     * @param array $arr The associative array to normalize.
     * @return array The array with normalized keys.
     * @throws \Psr\SimpleCache\InvalidArgumentException in case one of the keys is invalid.
     */
    private function redisNormalizeArrayKeysSerializeValue($arr): array
    {
        $result = array();
        foreach ($arr as $key => $value) {
            $keyNormalized = $this->redisValidateKey($key);
            $result[$keyNormalized] = serialize($value);
        }
        unset($key, $value, $keyNormalized);
        return $result;
    }

    /**
     * Normalize the values of an array like they were keys.
     * @param array $arr The array to normalize.
     * @return array array with normalized values.
     * @throws \Psr\SimpleCache\InvalidArgumentException in case one of the values is invalid as a key.
     */
    private function redisNormalizeArrayValuesLikeKeys($arr): array
    {
        $result = array();
        foreach ($arr as $key) {
            $result[] = $this->redisValidateKey($key);
        }
        return $result;
    }

    /**
     * Normalize TTL value.
     * @param int|\DateInterval|null $ttl The TTL to normalize.
     * @return null|int Integer in case the normalized TTL is greater than zero, null otherwise.
     * @throws \kbATeam\Cache\Exceptions\InvalidArgumentException in case the TTL is neither integer,
     *                                                            nor \DateInterval, nor null.
     */
    private function redisNormalizeTtl($ttl)
    {
        if ($ttl === null) {
            return null;
        }
        if ($ttl instanceof \DateInterval) {
            $ttl = (int) \DateTime::createFromFormat('U', 0)->add($ttl)->format('U');
        }
        if (is_int($ttl)) {
            return (0 < $ttl) ? $ttl : 0;
        }
        throw new InvalidArgumentTypeException('TTL', 'an integer, a \DateInterval or null', $ttl);
    }

    /**
     * Determine whether the given array is associative or not.
     * @param array $keys The array to check.
     * @return bool is associative?
     */
    public static function isValidKeysArray($keys): bool
    {
        //In case it's a traversable object, we're already done.
        if ($keys instanceof \Traversable) {
            return true;
        }

        //validate whether given argument is an array.
        if (!is_array($keys)) {
            return false;
        }

        //an empty array is valid!
        if (array() === $keys) {
            return true;
        }

        //associative arrays are not valid
        if (array_keys($keys) !== range(0, count($keys) - 1)) {
            return false;
        }

        return true;
    }
}
