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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->integer('max_doors')->default(0); // -1 untuk unlimited
            $table->string('door_label');
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('duration_days')->default(30);
            $table->string('billing_cycle')->default('monthly');
            $table->string('badge_text')->nullable();
            $table->string('discount_label')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->text('description');
            $table->json('features');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
