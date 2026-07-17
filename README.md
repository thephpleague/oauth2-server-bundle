# OAuth2 Server Bundle

![Unit tests status](https://github.com/thephpleague/oauth2-server-bundle/workflows/unit%20tests/badge.svg)
![Coding standards status](https://github.com/thephpleague/oauth2-server-bundle/workflows/coding%20standards/badge.svg)
[![Type Coverage](https://shepherd.dev/github/thephpleague/oauth2-server-bundle/coverage.svg)](https://shepherd.dev/github/thephpleague/oauth2-server-bundle)
[![Latest Stable Version](https://poser.pugx.org/league/oauth2-server-bundle/v/stable)](https://packagist.org/packages/league/oauth2-server-bundle)

OAuth2ServerBundle is a Symfony bundle integrating the [oauth2-server](https://github.com/thephpleague/oauth2-server) library into Symfony applications.

Replacement of trikoder/oauth2-bundle made in coordination with [trikoder](https://github.com/trikoder) and [Symfony](https://github.com/symfony/symfony) core team members in order to improve its maintenance, keep it in sync with Symfony developments and reduce the friction that vendor-overdiversification causes to end users.

## Versions

Active development happens on the `2.x` branch, which represents the 2.x major release. Please submit new features there.

The 1.x branch is in maintenance mode i.e. it accepts only bugfixes. Please target it if you're submitting a bugfix that applies to 1.x.

When upgrading from 1.x to 2.x, follow the [UPGRADE guide](https://github.com/thephpleague/oauth2-server-bundle/blob/2.x/UPGRADE-2.x.md), which lists the BC breaking changes to be aware of when upgrading to 2.0 or higher.

## Quick Start

1. Require the bundle using Composer:

    ```sh
    composer require league/oauth2-server-bundle
    ```

2. Require Doctrine to use it as persistence layer:

    ```sh
    composer require doctrine/doctrine-bundle doctrine/orm
    ```

## Documentation

The docs [can be found in the `docs/` directory](docs/index.md) of this repository.

## License
See the [LICENSE](LICENSE) file for copyrights and limitations (MIT).
