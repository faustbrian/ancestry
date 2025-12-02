<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Exceptions\InvalidConfigurationException;

describe('InvalidConfigurationException', function (): void {
    test('conflictingMorphKeyMaps creates exception with correct message', function (): void {
        $exception = InvalidConfigurationException::conflictingMorphKeyMaps();

        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe(
            'Cannot configure both "morphKeyMap" and "enforceMorphKeyMap". Choose one or the other.',
        );
    });

    test('missingAncestryType creates exception with correct message', function (): void {
        $exception = InvalidConfigurationException::missingAncestryType();

        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe('Ancestor type must be set. Call type() first.');
    });
});
