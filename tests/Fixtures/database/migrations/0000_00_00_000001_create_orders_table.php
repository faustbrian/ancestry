<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Ancestry\Enums\PrimaryKeyType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        /** @var string $configValue */
        $configValue = Config::get('ancestry.primary_key_type', 'id');
        $primaryKeyType = PrimaryKeyType::tryFrom($configValue) ?? PrimaryKeyType::Id;

        Schema::create('orders', function (Blueprint $table) use ($primaryKeyType): void {
            match ($primaryKeyType) {
                PrimaryKeyType::ULID => $table->ulid('id')->primary(),
                PrimaryKeyType::UUID => $table->uuid('id')->primary(),
                default => $table->id(),
            };

            $table->string('reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
