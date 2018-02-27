# kbA-Team cache

[![License: MIT][license-mit]](LICENSE)
[![Build Status][build-status-master]][travis-ci]
[![Maintainability][maintainability-badge]][maintainability]
[![Test Coverage][coverage-badge]][coverage]

Simple cache implementing [PSR-16: Common Interface for Caching Libraries][psr16] connecting to [Redis][redis] using [PhpRedis][phpredis].

## Why???

We are aware of the [PHP-Cache Project][phpcache] with all sorts of adapters- even for [PhpRedis][phpredis] **and** [Predis][predis]. When looking closely, all the Redis adapters implement PSR-6, not PSR-16. Furthermore, all the serious PSR-16 solutions we found, implement multiple storage backends but hardly ever Redis.

We were in need of a _simple cache solution just for Redis_ without all the bells and whistles of PSR-6 and without all the abstraction layers necessary to implement multiple storage backends.

## Usage

### Add to your project

```bash
composer require kba-team/cache "~1.0.0"
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

### Installation

```bash
composer install
```

### Run unit tests

```bash
vendor/bin/phpunit
```

[license-mit]: https://img.shields.io/badge/license-MIT-blue.svg
[build-status-master]: https://travis-ci.org/the-kbA-team/cache.svg?branch=master
[travis-ci]: https://travis-ci.org/the-kbA-team/cache
[maintainability-badge]: https://api.codeclimate.com/v1/badges/96a719b084cfe899e643/maintainability
[maintainability]: https://codeclimate.com/github/the-kbA-team/cache/maintainability
[coverage-badge]: https://api.codeclimate.com/v1/badges/96a719b084cfe899e643/test_coverage
[coverage]: https://codeclimate.com/github/the-kbA-team/cache/test_coverage
[psr16]: https://www.php-fig.org/psr/psr-16/
[redis]: https://redis.io/
[predis]: https://github.com/nrk/predis
[phpcache]: http://www.php-cache.com/en/latest/
[phpredis]: https://github.com/phpredis/phpredis
[semver]: https://semver.org/
