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
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('original_price');
            $table->unsignedBigInteger('rescue_price');
            $table->unsignedInteger('stock');
            $table->time('pickup_start');
            $table->time('pickup_end');
            $table->enum('status', ['DRAFT', 'AVAILABLE', 'SOLD_OUT', 'EXPIRED', 'INACTIVE'])->default('DRAFT');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['partner_id', 'status']);
            $table->index(['category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
