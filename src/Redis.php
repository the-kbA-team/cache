<?php

namespace kbATeam\Cache;

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
        $this->client = $client;
    }

    /**
     * Get the redis client application.
     * @return \Redis
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Return a redis cache object, that will connect via tcp to a redis server.
     * @param string $hostname Either hostname or IP address of the redis server.
     * @param int $database The database ID to use on the redis server.
     * @param string|null $password Optional password to access the redis server. Default: null
     * @param int $port Optional TCP port of the server. Default: 6379
     * @return \kbATeam\Cache\Redis An instance of this class connecting to the given server.
     * @throws \kbATeam\Cache\Exceptions\InvalidArgumentException In case any of the parameters is invalid.
     */
    public static function tcp($hostname, $database, $password = null, $port = 6379)
    {
        //validate hostname/IP (throws exception in case it's not valid)
        static::isHostnameValid($hostname);
        //validate database id
        if(!is_int($database) || 0 > $database) {
            throw new Exceptions\InvalidArgumentException("Database must be a positive integer!");
        }
        //validate password
        if(!is_null($password) && !is_string($password)) {
            throw new Exceptions\InvalidArgumentException("Password must be a string!");
        }
        $client = new \Redis();
        $client->pconnect($hostname, $port);
        if(!is_null($password)) {
            if(!$client->auth($password)){
                throw new Exceptions\InvalidArgumentException("Password authentication failed!");
            }
        }
        if(!$client->select($database)) {
            throw new Exceptions\InvalidArgumentException(sprintf("Invalid database index %u!", $database));
        }
        $client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
        return new self($client);
    }

    /**
     * Validates the given hostname and throws an exception in case it's not.
     * @param string $hostname The hostname to validate.
     * @return boolean Is the given hostname valid?
     * @throws \kbATeam\Cache\Exceptions\InvalidArgumentException in case the hostname is invalid.
     */
    protected static function isHostnameValid($hostname)
    {
        if ((
                //valid chars check
                !preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $hostname)
                //overall length check
                || !preg_match("/^.{1,253}$/", $hostname)
                //length of each label
                || !preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $hostname)
            )
            && !filter_var($hostname, FILTER_VALIDATE_IP)
        ) {
            throw new Exceptions\InvalidArgumentException(sprintf(
                "Invalid hostname/IP given: '%s'",
                $hostname
            ));
        }
        return true;
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
        $key_compat = $this->redisNormalizeKey($key);
        $result = $this->client->get($key_compat);
        if (empty($result)) {
            $result = $default;
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
        $key_compat = $this->redisNormalizeKey($key);
        $ttl_norm = $this->redisNormalizeTtl($ttl);
        if (is_null($ttl_norm)) {
            //no TTL
            $result = $this->client->set($key_compat, $value);
        } elseif (0 === $ttl_norm) {
            //ttl <= 0 means: delete!
            $result = $this->client->del(array($key_compat));
        } else {
            //set ttl
            $result = $this->client->setex($key_compat, $ttl_norm, $value);
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
        $key_compat = $this->redisNormalizeKey($key);
        $this->client->del(array($key_compat));
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
        if ($this->isAssoc($keys)) {
            throw new Exceptions\InvalidArgumentException("Keys must be an array!");
        }
        $result = array();
        foreach ($this->client->mget($keys) as $id => $value) {
            $result[$keys[$id]] = $value;
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
            throw new Exceptions\InvalidArgumentException(sprintf(
                'Values must be an array or an Iterator, "%s" given.',
                is_object($values) ? get_class($values) : gettype($values)
            ));
        }
        $ttl_norm = $this->redisNormalizeTtl($ttl);
        if(is_null($ttl_norm)) {
            //without ttl use redis mset() but normalize keys before
            $result = $this->client->mset(
                $this->redisNormalizeArrayKeys($values)
            );
        } elseif (0 === $ttl_norm) {
            //ttl <= 0 means delete the normalized keys from the array
            $result = $this->client->del(
                $this->redisNormalizeArrayValuesLikeKeys(array_keys($values))
            );
        } else {
            $result = true;
            foreach ($values as $key => $value) {
                $key_norm = $this->redisNormalizeKey($key);
                if(!$this->client->setex($key_norm, $ttl_norm, $value)) {
                    $result = false;
                    break;
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
        if ($this->isAssoc($keys)) {
            throw new Exceptions\InvalidArgumentException("Keys must be an array!");
        }
        $result = $this->client->del(
            $this->redisNormalizeArrayValuesLikeKeys($keys)
        );
        return (count($keys) == $result);
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
            $this->redisNormalizeKey($key)
        );
        return (1 === $result);
    }

    /**
     * Encode the string into a sting redis will accept as key.
     * @param string $str The string to encode into a valid redis key string.
     * @return string A valid redis key string.
     */
    private function encodeRedisKey($str)
    {
        return preg_replace("~[^a-zA-Z0-9_]~", "_", $str);
    }

    /**
     * Validate and encode any given string to a valid redis key as PSR-16 requests.
     * @param string|int $str The string to validate and encode into a valid redis key.
     * @return string A valid redis key.
     * @throws \Psr\SimpleCache\InvalidArgumentException in case the given string is invalid.
     */
    private function redisNormalizeKey($str)
    {
        if (!is_string($str) && !is_int($str)) {
            throw new Exceptions\InvalidArgumentException(sprintf(
                'Expected key to be a string, "%s" given!',
                is_object($str) ? get_class($str) : gettype($str)
            ));
        }
        if (mb_strlen($str) < 1) {
            throw new Exceptions\InvalidArgumentException("Given key is empty!");
        }
        return $this->encodeRedisKey($str);
    }

    /**
     * Normalize the keys of an array.
     * ATTENTION: This function receives a reference and returns a reference!
     * @param array $arr Referenced associative array to normalize.
     * @return array Referenceable array with normalized keys.
     * @throws \Psr\SimpleCache\InvalidArgumentException in case one of the keys is invalid.
     */
    private function &redisNormalizeArrayKeys(&$arr)
    {
        $result = array();
        foreach($arr as $key => $value) {
            $key_norm = $this->redisNormalizeKey($key);
            $result[$key_norm] = $value;
        }
        unset($key, $value, $key_norm);
        return $result;
    }

    /**
     * Normalize the values of an array like they were keys.
     * ATTENTION: This function receives a reference and returns a reference!
     * @param array $arr Referenced array to normalize.
     * @return array Referenceable array with normalized values.
     * @throws \Psr\SimpleCache\InvalidArgumentException in case one of the values is invalid as a key.
     */
    private function &redisNormalizeArrayValuesLikeKeys(&$arr)
    {
        $result = array();
        foreach($arr as $id => $key) {
            $result[$id] = $this->redisNormalizeKey($key);
        }
        unset($id, $key);
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
        if (is_null($ttl)) {
            return null;
        }
        if ($ttl instanceof \DateInterval) {
            $ttl = (int) \DateTime::createFromFormat('U', 0)->add($ttl)->format('U');
        }
        if (is_int($ttl)) {
            return (0 < $ttl) ? $ttl : 0;
        }
        throw new Exceptions\InvalidArgumentException(sprintf(
            'Time-to-live must either be an integer, a DateInterval or null, "%s" given',
            is_object($ttl) ? get_class($ttl) : gettype($ttl)
        ));
    }

    /**
     * Determine whether the given array is associative or not.
     * @param array $arr The array to check.
     * @return bool is associative?
     * @throws \kbATeam\Cache\Exceptions\InvalidArgumentException in case not array was given.
     */
    private function isAssoc($arr)
    {
        //validate whether given argument is an array.
        if (!is_array($arr)) {
            throw new Exceptions\InvalidArgumentException("Must be an array!");
        }
        //an empty array is no associative array!
        if (array() === $arr) {
            return false;
        }
        return (array_keys($arr) !== range(0, count($arr) - 1));
    }
}
