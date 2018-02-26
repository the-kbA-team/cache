<?php

namespace kbATeam\Cache;

use Predis\Client;
use Predis\ClientInterface;

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
     * @var \Predis\ClientInterface
     */
    protected $client;

    /**
     * Redis simple cache constructor.
     * @param \Predis\ClientInterface $client The redis client to connect to handle the redis connection.
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Get the redis client application.
     * @return \Predis\ClientInterface
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
        $config = array(
            'scheme' => 'tcp',
            'host' => $hostname,
            'port' => $port
        );
        $options = static::buildClientOptions($database, $password);
        return new self(new Client($config, $options));
    }

    /**
     * Connect to a cluster of redis servers.
     * @param array $hostnames The hostnames/IPs of the cluster.
     * @param int $database The database ID to use on the redis server.
     * @param string|null  $password Optional password to access the redis cluster. Default: null
     * @return \kbATeam\Cache\Redis An instance of this class connecting to the given cluster.
     * @throws \kbATeam\Cache\Exceptions\InvalidArgumentException In case any of the parameters is invalid.
     */
    public static function cluster(array $hostnames, $database, $password = null)
    {
        $config = array();
        //validate hostnames (throws exception in case they're not valid)
        foreach ($hostnames as $hostname) {
            static::isHostnameValid($hostname);
            $config[] = sprintf("tcp://%s", $hostname);
        }
        //build options
        $options = static::buildClientOptions($database, $password, true);
        return new self(new Client($config, $options));
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
     * Build options for Predis\Client.
     * @param int $database The database ID to use on the redis server(s).
     * @param string|null $password Optional password to access the redis server(s).
     * @param bool $cluster Optional flag whether to set the cluster option or not.
     * @return array Options array for Predis\Client.
     * @throws \kbATeam\Cache\Exceptions\InvalidArgumentException In case any of the parameters is invalid.
     */
    protected static function buildClientOptions($database, $password = null, $cluster = false)
    {
        //validate database being an integer
        if (!is_integer($database) || $database < 1) {
            throw new Exceptions\InvalidArgumentException("Database has to be an integer!");
        }
        $result = array(
            'parameters' => array(
                'database' => $database
            )
        );
        //add password in case it's required
        if (!empty($password)) {
            $result['parameters']['password'] = $password;
        }
        //validate cluster parameter being a boolean
        if (!is_bool($cluster)) {
            // @codeCoverageIgnoreStart
            throw new Exceptions\InvalidArgumentException("The cluster flag has to be a boolean!");
            // @codeCoverageIgnoreEnd
        }
        //add option for cluster in case it's set
        if (true === $cluster) {
            $result['cluster'] = 'redis';
        }
        return $result;
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
        $key_compat = $this->redisKeyCompat($key);
        $result_ser = $this->client->get($key_compat);
        if (empty($result_ser)) {
            $result = $default;
        } else {
            $result = unserialize($result_ser);
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
        $key_compat = $this->redisKeyCompat($key);
        $value_ser = serialize($value);
        if (is_null($ttl)) {
            $result = $this->client->setnx($key_compat, $value_ser);
        } elseif (is_integer($ttl)) {
            $result = $this->client->setex($key_compat, $ttl, $value_ser);
        } elseif ($ttl instanceof \DateInterval) {
            $result = $this->client->setex($key_compat, $ttl->s, $value_ser);
        } else {
            throw new Exceptions\InvalidArgumentException(
                "TTL must either be null, an integer or an instance of \DateInterval!"
            );
        }
        return ('OK' === $result);
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
        $key_compat = $this->redisKeyCompat($key);
        $result = $this->client->del($key_compat);
        return (1 == $result);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        $this->client->flushdb();
        return true; //never fails
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
        if (!is_array($keys)) {
            throw new Exceptions\InvalidArgumentException("Keys must be an array!");
        }
        $result = array();
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
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
        if (!is_array($values)) {
            throw new Exceptions\InvalidArgumentException(
                "Values must be an associative array of key=>value!"
            );
        }
        try {
            foreach ($values as $key => $value) {
                if (!$this->set($key, $value, $ttl)) {
                    throw new Exceptions\CacheException();
                }
            }
        } catch (Exceptions\CacheException $e) {
            return false;
        }
        return true;
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
        if (!is_array($keys)) {
            throw new Exceptions\InvalidArgumentException("Keys must be an array!");
        }
        try {
            foreach ($keys as $key) {
                if (!$this->delete($key)) {
                    throw new Exceptions\CacheException();
                }
            }
        } catch (Exceptions\CacheException $e) {
            return false;
        }
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
        $key_compat = $this->redisKeyCompat($key);
        $result = $this->client->exists($key_compat);
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
     * @param string $str The string to validate and encode into a valid redis key.
     * @return string A valid redis key.
     * @throws \Psr\SimpleCache\InvalidArgumentException in case the given string is invalid.
     */
    private function redisKeyCompat($str)
    {
        if (!is_string($str)) {
            throw new Exceptions\InvalidArgumentException("Given key is not a string!");
        }
        if (empty($str)) {
            throw new Exceptions\InvalidArgumentException("Given key is empty!");
        }
        return $this->encodeRedisKey($str);
    }
}
