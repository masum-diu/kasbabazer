<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Phone/WhatsApp number buyers can call for this listing; shown to signed-in users only. */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('contact_phone', 20)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('contact_phone');
        });
    }
};
