<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->decimal('rounding_discount', 15, 2)->default(0)->after('discount');
            $table->boolean('price_includes_tax')->default(false)->after('rounding_discount');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['rounding_discount', 'price_includes_tax']);
        });
    }
};
