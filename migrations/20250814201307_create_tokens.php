<?php

declare(strict_types=1);

use Radix\Database\Migration\Blueprint;
use Radix\Database\Migration\Schema;

return new class {
    public function up(Schema $schema): void
    {
        $schema->create('tokens', function (Blueprint $table) {
            $table->id(); // Primärnyckel
            $table->integer('user_id', true); // Användarens ID
            $table->string('value', 128); // API-token
            $table->string('description', 255, ['nullable' => true]); // Beskrivning (valfritt, t.ex. "Token for admin panel")
            $table->datetime('expires_at', ['nullable' => true]); // Utgångsdatum (tillåter null)

            $table->timestamps(); // Skapar 'created_at' och 'updated_at'
            $table->unique(['value']); // Unikconstraint på token
            $table->foreign('user_id', 'users', 'id', 'cascade', 'cascade');
        });
    }


    public function down(Schema $schema): void
    {
        $schema->dropIfExists('tokens');
    }
};