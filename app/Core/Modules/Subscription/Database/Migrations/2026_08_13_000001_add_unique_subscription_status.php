<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unique (tenant_id, app_id, status) mencegah double-subscribe aktif
 * (race condition di SubscriptionController::store) di level database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unique(['tenant_id', 'app_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'app_id', 'status']);
        });
    }
};
