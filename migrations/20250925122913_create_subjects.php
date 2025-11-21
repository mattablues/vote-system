<?php

declare(strict_types=1);

use Radix\Database\Migration\Blueprint;
use Radix\Database\Migration\Schema;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('subjects', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id', true);
            $table->text('subject');
            $table->tinyInteger('published');
            $table->timestamps();
            $table->index(['category_id']);
            $table->foreign('category_id', 'categories', 'id', 'cascade', 'cascade');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('subjects');
    }
};
