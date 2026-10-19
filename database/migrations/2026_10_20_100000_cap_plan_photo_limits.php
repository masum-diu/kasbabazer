<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** No listing may carry more than 5 photos, so no plan should advertise more. */
    public function up(): void
    {
        DB::table('plans')->where('photo_limit', '>', 5)->update(['photo_limit' => 5]);
    }

    public function down(): void
    {
        // The previous per-plan limits are not recoverable; leave the data as is.
    }
};
