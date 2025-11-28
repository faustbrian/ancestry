<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Facades\Ancestry;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Order;
use Tests\Fixtures\User;
use Tests\TestCase;

pest()->extend(TestCase::class)->in(__DIR__);

/**
 * Create a test user.
 */
function user(array $attributes = []): User
{
    return User::query()->create($attributes);
}

/**
 * Create a hierarchy chain of users.
 *
 * @param  int              $count Number of users in the chain
 * @param  string           $type  Ancestor type
 * @return array<int, User>
 */
function createAncestorChain(int $count, string $type = 'seller'): array
{
    $users = [];
    $parent = null;

    for ($i = 0; $i < $count; ++$i) {
        $user = user();
        Ancestry::addToAncestry($user, $type, $parent);
        $users[] = $user;
        $parent = $user;
    }

    return $users;
}

/**
 * Get the morph key value for a model.
 */
function getModelKey(Model $model): mixed
{
    $morphType = config('ancestry.ancestor_morph_type', 'morph');

    return match ($morphType) {
        'uuidMorph' => $model->uuid,
        'ulidMorph' => $model->ulid,
        default => $model->id,
    };
}

/**
 * Create a test order.
 */
function order(array $attributes = []): Order
{
    return Order::query()->create($attributes);
}
