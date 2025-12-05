<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Exceptions\ConflictingMorphKeyMapsException;
use Cline\Ancestry\Exceptions\InvalidConfigurationException;
use Cline\Ancestry\Exceptions\MissingAncestryTypeException;

describe('ConflictingMorphKeyMapsException', function (): void {
    test('detected creates exception with correct message', function (): void {
        $exception = ConflictingMorphKeyMapsException::detected();

        expect($exception)->toBeInstanceOf(ConflictingMorphKeyMapsException::class);
        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe(
            'Cannot configure both "morphKeyMap" and "enforceMorphKeyMap". Choose one or the other.',
        );
    });
});

describe('MissingAncestryTypeException', function (): void {
    test('forOperation creates exception with correct message', function (): void {
        $exception = MissingAncestryTypeException::forOperation();

        expect($exception)->toBeInstanceOf(MissingAncestryTypeException::class);
        expect($exception)->toBeInstanceOf(InvalidConfigurationException::class);
        expect($exception->getMessage())->toBe('Ancestor type must be set. Call type() first.');
    });
});
