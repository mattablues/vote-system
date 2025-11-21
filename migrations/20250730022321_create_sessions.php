<?php

declare(strict_types=1);

use Radix\Database\Migration\Blueprint;
use Radix\Database\Migration\Schema;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('sessions', function (Blueprint $table) {
            $table->string('id', 128, ['nullable' => false]); // Definiera en unik kolumn för session-ID
            $table->text('data', ['nullable' => true]); // Sessionsdata kan vara null
            $table->integer('expiry', true, ['nullable' => false]); // UNIX-tidsstämpel utan null-värden
            $table->primary(['id']); // Gör ID till primärnyckeln
            $table->timestamps(); // Lägg till created_at och updated_at
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('sessions');
    }
};
