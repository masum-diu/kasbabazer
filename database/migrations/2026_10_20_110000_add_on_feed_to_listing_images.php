<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Photos the seller chose to show on the home feed (1 to 3 per listing). */
    public function up(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->boolean('on_feed')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('listing_images', function (Blueprint $table) {
            $table->dropColumn('on_feed');
        });
    }
};
