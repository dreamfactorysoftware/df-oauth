®# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Fixed
- DF-1114 - Fixed OAuth issue with PHP 5.6

## [0.9.0] - 2017-04-21
### Changed
- Use new service config handling from df-core

### Added
- DF-895 Added support for username based authentication

## [0.8.0] - 2017-03-03
- Major restructuring to upgrade to Laravel 5.4 and be more dynamically available

## [0.7.0] - 2017-01-16
### Changed
- OAuth callback handler now checks for service name using state identifier when service name is not present on callback url.

## [0.6.0] - 2016-11-17
- Dependency changes only

## [0.5.0] - 2016-10-03
### Added
- DF-833 Returning OAuth token after OAuth authentication
- DF-425 Allowing configurable role per app for open registration, OAuth, and AD/Ldap services

### Changed
- Promoted laravel socialite providers to 'socialiteproviders' classes
- DF-826 Protecting client_secret

## [0.4.0] - 2016-08-21
### Added
- Support for Microsoft Live Socialite extension

### Changed
- General cleanup from declaration changes in df-core for service doc and providers

## [0.3.1] - 2016-07-08
### Changed
- General cleanup from declaration changes in df-core

## [0.3.0] - 2016-05-27
### Changed
- Moved seeding functionality to service provider to adhere to df-core changes

## [0.2.1] - 2016-02-02
### Fixed
- OAuth redirect response

## [0.2.0] - 2016-01-29
### Added

### Changed
- **MAJOR** Updated code base to use OpenAPI (fka Swagger) Specification 2.0 from 1.2

### Fixed

## [0.1.1] - 2015-12-19
### Added
- Support for LinkedIn OAuth

## 0.1.0 - 2015-10-24
First official release working with the new [dreamfactory](https://github.com/dreamfactorysoftware/dreamfactory) project.

[Unreleased]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.9.0...HEAD
[0.9.0]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.8.0...0.9.0
[0.8.0]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.7.0...0.8.0
[0.7.0]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.6.0...0.7.0
[0.6.0]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.5.0...0.6.0
[0.5.0]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.4.0...0.5.0
[0.4.0]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.3.1...0.4.0
[0.3.1]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.2.1...0.3.0
[0.2.1]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.1.1...0.2.0
[0.1.1]: https://github.com/dreamfactorysoftware/df-oauth/compare/0.1.0...0.1.1
