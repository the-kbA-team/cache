# kbA-Team cache

[![License: MIT][license-mit]](LICENSE)
[![Build Status][build-status-master]][travis-ci]
[![Maintainability][maintainability-badge]][maintainability]
[![Test Coverage][coverage-badge]][coverage]

Simple cache using [Redis][redis] implementing [PSR-16: Common Interface for Caching Libraries][psr16].

## Usage

### Add to your project

```bash
composer require kba-team/cache
```

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

### Redis cluster via TCP

```php
<?php
//create object to access the redis server
$redis = \kbATeam\Cache\Redis::cluster(['redis-server1', 'redis-server2'], 10);
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
[coverage]: https://codeclimate.com/github/the-kbA-team/cache/test_coverage****
[psr16]: https://www.php-fig.org/psr/psr-16/
[redis]: https://redis.io/
