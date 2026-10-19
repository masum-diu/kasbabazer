<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('price')->default(0); // BDT per period
            $table->unsignedInteger('listing_limit')->nullable(); // null = unlimited
            $table->unsignedTinyInteger('photo_limit')->default(3);
            $table->unsignedInteger('boosts_per_period')->default(0);
            $table->boolean('verified_badge')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->string('status', 20)->default('pending'); // pending|active|expired|rejected
            $table->unsignedInteger('amount');
            $table->string('payment_method', 20)->default('bkash');
            $table->string('sender_number', 20)->nullable();
            $table->string('transaction_id', 40)->nullable()->unique();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'ends_at']);
        });

        Schema::create('boosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('boosted_until')->nullable()->index();
        });

        Schema::table('seller_profiles', function (Blueprint $table) {
            // True when the verified tick comes from a paid plan (and so must lapse with it).
            $table->boolean('verified_by_plan')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', fn (Blueprint $t) => $t->dropColumn('verified_by_plan'));
        Schema::table('listings', fn (Blueprint $t) => $t->dropColumn('boosted_until'));
        Schema::dropIfExists('boosts');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
