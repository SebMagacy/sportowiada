<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropUnique(['owner_id', 'discipline_id', 'name']);
            $table->dropColumn('owner_id');
            $table->unique(['discipline_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException(
            'Nie można automatycznie odtworzyć usuniętych właścicieli lig.'
        );
    }
};
