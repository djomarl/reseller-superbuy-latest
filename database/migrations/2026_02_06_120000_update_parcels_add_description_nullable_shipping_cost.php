<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->text('description')->nullable()->after('tracking_code');
            $table->decimal('shipping_cost', 10, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->decimal('shipping_cost', 10, 2)->default(0)->nullable(false)->change();
        });
    }
};
