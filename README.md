# kbA-Team cache

[![License: MIT][license-mit]](LICENSE)
[![Build Status][build-status-php5]][travis-ci]

Simple cache implementing [PSR-16: Common Interface for Caching Libraries][psr16] connecting to [Redis][redis] using [PhpRedis][phpredis].

## Why???

We are aware of the [PHP-Cache Project][phpcache] with all sorts of adapters- even for [PhpRedis][phpredis] **and** [Predis][predis].

We were in need of a _simple cache solution just for Redis_ without all the bells and whistles of PSR-6 and without all the abstraction layers necessary to implement multiple storage backends.

## Usage

### Add to your project

```bash
composer require kba-team/cache "^1.0"
```

We use [Semver][semver].

### Single redis server via TCP

```php
<?php
//create object to access the redis server
$redis = \kbATeam\Cache\Redis::tcp('redis-server', 10);
//store value in redis server
if (!$redis->has('hello')) {
    $redis->set('hello', 'Hello World!');
}
//retrieve value from redis server
echo $redis->get('hello');
```

## Testing

Testing requires a running redis server.

### Installation

```bash
composer install
```

### Run unit tests

A running redis server is required by the unit tests.

```bash
vendor/bin/phpunit
```

You can set the following _environment variables_ to override the default values expected by the unit tests.

* `REDIS_SERVER_HOST`: The hostname or IP address of the redis server. Default: `127.0.0.1`
* `REDIS_SERVER_PORT`: The TCP port the redis server is listening on. Default: `6379` 
* `REDIS_SERVER_DBINDEX`: The database to use on the redis server. Default: `0`
* `REDIS_SERVER_PASSWORD`: The password used to access the redis server. Default: no password.

### Starting a redis docker container

```bash
docker run \
    --rm \
    --init \
    --detach \
    --name redis-server \
    redis:3.0
```

Get the containers' IP address:

```bash
docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' redis-server
```

[license-mit]: https://img.shields.io/badge/license-MIT-blue.svg
[build-status-php5]: https://travis-ci.org/the-kbA-team/cache.svg?branch=php5
[travis-ci]: https://travis-ci.org/the-kbA-team/cache
[psr16]: https://www.php-fig.org/psr/psr-16/
[redis]: https://redis.io/
[predis]: https://github.com/nrk/predis
[phpcache]: http://www.php-cache.com/en/latest/
[phpredis]: https://github.com/phpredis/phpredis
[semver]: https://semver.org/
