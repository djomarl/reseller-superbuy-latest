<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Parcels Tabel
        Schema::create('parcels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Koppeling aan gebruiker
            $table->string('parcel_no');
            $table->string('tracking_code')->nullable();
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->enum('status', ['prep', 'shipped', 'arrived'])->default('prep');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps(); // created_at & updated_at
        });

        // 2. Items Tabel
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parcel_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('item_no')->default('-');
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('size')->nullable();
            $table->string('category')->default('Overige');
            
            $table->decimal('buy_price', 10, 2)->default(0);
            $table->decimal('sell_price', 10, 2)->nullable();
            
            $table->boolean('is_sold')->default(false);
            $table->date('sold_date')->nullable();
            $table->string('status')->default('todo'); // todo, online, reserved, sold
            $table->timestamp('status_changed_at')->useCurrent();
            
            $table->text('image_url')->nullable(); // We slaan URL op, of base64 (maar liever file upload)
            $table->string('qc_link')->nullable();
            $table->string('source_link')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // 3. Templates Tabel
        Schema::create('item_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('category')->nullable();
            $table->decimal('default_buy_price', 10, 2)->nullable();
            $table->decimal('default_sell_price', 10, 2)->nullable();
            $table->string('default_qc_link')->nullable();
            $table->text('image_url')->nullable();
            $table->timestamps();
        });
        
        // 4. Settings Tabel (voor algemene kosten per user)
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('general_costs', 10, 2)->default(0);
            $table->decimal('exchange_rate', 8, 4)->default(0.95);
            $table->decimal('profit_goal', 10, 2)->default(500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('item_templates');
        Schema::dropIfExists('items');
        Schema::dropIfExists('parcels');
    }
};