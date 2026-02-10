<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryToItemsAndTemplates extends Migration
{
	public function up(): void
	{
		Schema::table('items', function (Blueprint $table) {
			if (!Schema::hasColumn('items', 'category')) {
				$table->string('category')->nullable()->after('size');
			}
		});

		Schema::table('item_templates', function (Blueprint $table) {
			if (!Schema::hasColumn('item_templates', 'category')) {
				$table->string('category')->nullable()->after('brand');
			}
		});
	}

	public function down(): void
	{
		Schema::table('items', function (Blueprint $table) {
			if (Schema::hasColumn('items', 'category')) {
				$table->dropColumn('category');
			}
		});

		Schema::table('item_templates', function (Blueprint $table) {
			if (Schema::hasColumn('item_templates', 'category')) {
				$table->dropColumn('category');
			}
		});
	}
}

