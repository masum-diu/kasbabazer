<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Services, one-off items and the like have no stock count, so quantity is optional. */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->nullable(false)->change();
        });
    }
};
