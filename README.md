# Amp SSH

[![Build Status](https://img.shields.io/travis/amphp/ssh/master.svg?style=flat-square)](https://travis-ci.org/amphp/ssh)
[![CoverageStatus](https://img.shields.io/coveralls/amphp/ssh/master.svg?style=flat-square)](https://coveralls.io/github/amphp/ssh?branch=master)
![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)

`amphp/ssh` provides asynchronous SSH client for [Amp](https://github.com/amphp/amp).

## Installation

This package can be installed as a [Composer](https://getcomposer.org/) dependency.

```bash
composer require amphp/ssh
```

## Requirements

- PHP 7.0+
- [libsodium extension](https://github.com/jedisct1/libsodium-php), included by default in PHP since 7.2

## Examples

See the `examples` directory.

## Versioning

`amphp/ssh` follows the [semver](http://semver.org/) semantic versioning specification like all other `amphp` packages.

## License

The MIT License (MIT). Please see [`LICENSE`](./LICENSE) for more information.

## Credits

A lot of work on this lib would not have been possible with previous awesome folks implementing this specification in PHP:

 * [PHPSeclib](https://github.com/phpseclib/phpseclib)
 * [PHP Encrypted Streams](https://github.com/jeskew/php-encrypted-streams)
