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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('questionnaire_id')->constrained('questionnaires')->cascadeOnDelete();
            $table->enum('type', ['text', 'textarea', 'radio', 'checkbox', 'select', 'number', 'date', 'email']);
            $table->text('question');
            $table->boolean('required')->default(false);
            $table->integer('order')->default(0);
            $table->json('options')->nullable();
            $table->foreignId('conditional_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->string('conditional_value', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
