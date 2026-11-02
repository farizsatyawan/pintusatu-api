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
        if (Schema::hasTable('room_types')) {
            Schema::table('room_types', function (Blueprint $table) {
                if (Schema::hasColumn('room_types', 'room_size')) {
                    $table->dropColumn('room_size');
                }
                if (Schema::hasColumn('room_types', 'default_price')) {
                    $table->dropColumn('default_price');
                }
            });
        }

        if (Schema::hasTable('rooms')) {
            Schema::table('rooms', function (Blueprint $table) {
                if (! Schema::hasColumn('rooms', 'room_size')) {
                    $table->string('room_size')->default('12 m²')->after('floor');
                }
                if (! Schema::hasColumn('rooms', 'price')) {
                    $table->unsignedBigInteger('price')->default(1500000)->after('room_size');
                }
                if (Schema::hasColumn('rooms', 'custom_price')) {
                    $table->dropColumn('custom_price');
                }
                if (Schema::hasColumn('rooms', 'facilities')) {
                    $table->dropColumn('facilities');
                }
                if (Schema::hasColumn('rooms', 'room_type')) {
                    $table->dropColumn('room_type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('room_types')) {
            Schema::table('room_types', function (Blueprint $table) {
                if (! Schema::hasColumn('room_types', 'room_size')) {
                    $table->string('room_size')->nullable();
                }
                if (! Schema::hasColumn('room_types', 'default_price')) {
                    $table->unsignedBigInteger('default_price')->nullable();
                }
            });
        }

        if (Schema::hasTable('rooms')) {
            Schema::table('rooms', function (Blueprint $table) {
                if (Schema::hasColumn('rooms', 'price')) {
                    $table->dropColumn('price');
                }
                if (Schema::hasColumn('rooms', 'room_size')) {
                    $table->dropColumn('room_size');
                }
            });
        }
    }
};

