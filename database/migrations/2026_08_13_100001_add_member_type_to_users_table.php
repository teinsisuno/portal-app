<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah tipe member: individu, umkm, perusahaan (FR user management manual).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('member_type')->default('individu')->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('member_type');
        });
    }
};
