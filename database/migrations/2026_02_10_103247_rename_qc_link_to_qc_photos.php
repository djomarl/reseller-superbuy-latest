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
        Schema::table('items', function (Blueprint $table) {
            $table->text('qc_photos')->nullable()->after('image_url');
        });

        // Kopieer bestaande data
        try {
            \DB::statement('UPDATE items SET qc_photos = qc_link WHERE qc_link IS NOT NULL AND qc_link != ""');
        } catch (\Exception $e) {}

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('qc_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('qc_link')->nullable()->after('image_url');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('qc_photos');
        });
    }
};
