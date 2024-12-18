<?php

use App\Enums\RecipeStatus;
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
        Schema::create('recipes', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('status', [
                RecipeStatus::SUBMITTED->value,
                RecipeStatus::APPROVED->value,
                RecipeStatus::REJECTED->value,
                RecipeStatus::HIDDEN->value
            ])->default(RecipeStatus::SUBMITTED->value);
            $table->text('reason')->nullable();
            $table->text('image_url')->nullable();
            $table->bigInteger('method_id');
            $table->text('youtube_url')->nullable();
            $table->jsonb('configs')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
