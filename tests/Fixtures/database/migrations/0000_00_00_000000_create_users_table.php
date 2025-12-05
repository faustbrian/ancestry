<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $keyType = config('ancestry.primary_key_type', 'id');

            match ($keyType) {
                'ulid' => $table->ulid('id')->primary(),
                'uuid' => $table->uuid('id')->primary(),
                default => $table->id(),
            };

            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
