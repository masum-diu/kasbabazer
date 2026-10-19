<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->string('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_admin')->default(false);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
            $table->dropColumn(['username', 'phone', 'avatar', 'bio', 'is_active', 'is_admin', 'deleted_at']);
        });
    }
};
