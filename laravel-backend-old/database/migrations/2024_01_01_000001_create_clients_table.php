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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['Active', 'Suspended', 'Inactive'])->default('Active');
            $table->timestamp('registration_date')->useCurrent();
            $table->timestamp('last_login')->nullable();
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->integer('active_services')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['email', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
