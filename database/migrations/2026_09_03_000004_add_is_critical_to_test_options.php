<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Variant "xavfli" deb belgilansa, talaba uni tanlaganda zudlik bilan
     * ogohlantirish yaratiladi.
     */
    public function up(): void
    {
        Schema::table('test_options', function (Blueprint $table) {
            $table->boolean('is_critical')->default(false)->after('option_value');
            $table->index('is_critical');
        });
    }

    public function down(): void
    {
        Schema::table('test_options', function (Blueprint $table) {
            $table->dropIndex(['is_critical']);
            $table->dropColumn('is_critical');
        });
    }
};
