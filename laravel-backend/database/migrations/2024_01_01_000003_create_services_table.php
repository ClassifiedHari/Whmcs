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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('product_name');
            $table->string('domain')->nullable();
            $table->enum('status', ['Active', 'Suspended', 'Pending', 'Terminated'])->default('Pending');
            $table->date('next_due_date');
            $table->decimal('recurring_amount', 10, 2);
            $table->enum('billing_cycle', ['Monthly', 'Annually', 'Quarterly', 'Biannually'])->default('Monthly');
            $table->timestamp('registration_date')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['client_id', 'status']);
            $table->index('status');
            $table->index('next_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
