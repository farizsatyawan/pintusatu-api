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
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
                $table->string('order_id')->unique();
                $table->unsignedBigInteger('gross_amount');
                $table->string('status')->default('pending'); // pending, paid, expired, cancelled
                $table->string('snap_token')->nullable();
                $table->text('snap_redirect_url')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
