<?php

declare(strict_types=1);

use Radix\Database\Migration\Blueprint;
use Radix\Database\Migration\Schema;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 128);
            $table->string('last_name', 128);
            $table->string('email');
            $table->string('password');
            $table->string('avatar', options: ['default' => '/images/graphics/avatar.png']);
            $table->enum('role', ['user', 'support', 'editor', 'moderator', 'admin'], ['default' => 'user']);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['id', 'role']);
            $table->unique(['email']);
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('users');
    }
};