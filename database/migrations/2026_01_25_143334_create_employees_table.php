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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_id', 50)->unique()->index('idx_employee_id');
            $table->string('first_name', 255);
            $table->string('last_name', 255);
            $table->string('email', 255)->nullable()->unique();
            $table->string('phone', 8)->unique()->index('idx_phone');
            $table->enum('position', ['employer', 'superviseur', 'chef_superviseur', 'manager'])->index('idx_position');
            $table->string('department', 255)->nullable()->index('idx_department');
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete()->index('idx_manager_id');
            $table->decimal('salary', 10, 2)->nullable();
            $table->date('hire_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'terminated'])->default('active')->index('idx_status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
