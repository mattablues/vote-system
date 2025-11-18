<?php

declare(strict_types=1);

use Radix\Database\Migration\Blueprint;
use Radix\Database\Migration\Schema;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('voters', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('password');
            $table->string('password_reset', 64, ['nullable' => true]);
            $table->dateTime('reset_expires_at', ['nullable' => true]);
            $table->string('activation', 64, ['nullable' => true]);
            $table->enum('status', ['activate', 'activated', 'blocked'], ['default' => 'activate']);
            $table->timestamps();
            $table->unique(['email']);
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('voters');
    }
};