<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\CodingStandard\Rector\Factory;
use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;

return Factory::create(
    paths: [__DIR__.'/src', __DIR__.'/tests'],
    skip: [
        RemoveUnreachableStatementRector::class => [__DIR__.'/tests'],
        NewlineBetweenClassLikeStmtsRector::class => [
            __DIR__.'/src/Database/Ancestor.php',
            __DIR__.'/src/Database/AncestorSnapshot.php',
            __DIR__.'/tests/Fixtures/Order.php',
            __DIR__.'/tests/Fixtures/User.php',
        ],
    ],
);
