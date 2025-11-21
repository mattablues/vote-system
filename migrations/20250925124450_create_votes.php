<?php

declare(strict_types=1);

use Radix\Database\Migration\Blueprint;
use Radix\Database\Migration\Schema;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('votes', function (Blueprint $table) {
            $table->id();
            $table->integer('subject_id', true);
            $table->integer('voter_id', true);
            $table->tinyInteger('vote', true);
            $table->datetime('voted_at', ['default' => 'CURRENT_TIMESTAMP']);
            $table->unique(['subject_id', 'voter_id']);
            $table->foreign('subject_id', 'subjects', 'id', 'cascade', 'cascade');
            $table->foreign('voter_id', 'voters', 'id', 'cascade', 'cascade');
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('votes');
    }
};
