<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('contact_whatsapp', 20)->nullable()->after('contact_phone');
            $table->string('contact_email', 120)->nullable()->after('contact_whatsapp');
            $table->string('contact_address', 200)->nullable()->after('contact_email');
            $table->string('contact_facebook', 255)->nullable()->after('contact_address');
            $table->string('contact_website', 255)->nullable()->after('contact_facebook');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['contact_whatsapp', 'contact_email', 'contact_address', 'contact_facebook', 'contact_website']);
        });
    }
};
