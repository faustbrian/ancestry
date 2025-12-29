[![GitHub Workflow Status][ico-tests]][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

------

# Ancestry

Closure table hierarchies for Eloquent models with O(1) ancestor/descendant queries.

Ancestry implements the closure table pattern for managing hierarchical relationships in Laravel. This enables efficient queries for ancestors and descendants without recursion limits, supporting deeply nested relationships like organizational charts, sales hierarchies, and category trees.

## Documentation

- [Getting Started](cookbook/getting-started.md) - Installation, requirements, and quick start
- [Basic Usage](cookbook/basic-usage.md) - Core operations and trait methods
- [Fluent API](cookbook/fluent-api.md) - Chainable, expressive interface
- [Configuration](cookbook/configuration.md) - Customize keys, morphs, depth limits
- [Multiple Hierarchy Types](cookbook/multiple-types.md) - One model, many hierarchies
- [Custom Key Mapping](cookbook/custom-key-mapping.md) - Advanced key configuration
- [Events](cookbook/events.md) - React to hierarchy changes
- [Snapshots](cookbook/snapshots.md) - Capture point-in-time hierarchy state

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form][link-security] rather than the issue queue.

## Credits

- [Brian Faust][link-maintainer]
- [All Contributors][link-contributors]

## License

The MIT License. Please see [License File](LICENSE.md) for more information.

[ico-tests]: https://git.cline.sh/faustbrian/ancestry/actions/workflows/quality-assurance.yaml/badge.svg
[ico-version]: https://img.shields.io/packagist/v/cline/ancestry.svg
[ico-license]: https://img.shields.io/badge/License-MIT-green.svg
[ico-downloads]: https://img.shields.io/packagist/dt/cline/ancestry.svg

[link-tests]: https://git.cline.sh/faustbrian/ancestry/actions
[link-packagist]: https://packagist.org/packages/cline/ancestry
[link-downloads]: https://packagist.org/packages/cline/ancestry
[link-security]: https://git.cline.sh/faustbrian/ancestry/security
[link-maintainer]: https://git.cline.sh/faustbrian
[link-contributors]: ../../contributors
