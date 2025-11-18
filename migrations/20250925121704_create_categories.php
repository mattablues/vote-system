<?php

declare(strict_types=1);

use Radix\Database\Migration\Blueprint;
use Radix\Database\Migration\Schema;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('categories');
    }
};