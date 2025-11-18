<?php

declare(strict_types=1);

use Radix\Database\Migration\Blueprint;
use Radix\Database\Migration\Schema;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('status', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id', true);
            $table->string('password_reset', 64, ['nullable' => true]);
            $table->dateTime('reset_expires_at', ['nullable' => true]);
            $table->string('activation', 64, ['nullable' => true]);
            $table->enum('status', ['activate', 'activated', 'blocked', 'closed'], ['default' => 'activate']);
            $table->enum('active', ['offline', 'online'], ['default' => 'offline']);
            $table->integer('active_at', true, ['nullable' => true]);
            $table->timestamps();
            $table->unique(['user_id']);
            $table->foreign('user_id', 'users', 'id', 'cascade', 'cascade');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('status');
    }
};