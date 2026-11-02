<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'nama_kos')) {
                $table->string('nama_kos')->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp')->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'jumlah_pintu')) {
                $table->integer('jumlah_pintu')->default(0)->after('whatsapp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'jumlah_pintu')) {
                $table->dropColumn('jumlah_pintu');
            }
            if (Schema::hasColumn('users', 'whatsapp')) {
                $table->dropColumn('whatsapp');
            }
            if (Schema::hasColumn('users', 'nama_kos')) {
                $table->dropColumn('nama_kos');
            }
        });
    }
};
