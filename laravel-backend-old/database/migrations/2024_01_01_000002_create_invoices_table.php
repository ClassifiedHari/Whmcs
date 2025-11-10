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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id')->unique();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['Paid', 'Pending', 'Overdue', 'Cancelled'])->default('Pending');
            $table->date('due_date');
            $table->timestamp('issue_date')->useCurrent();
            $table->text('description');
            $table->string('payment_method')->nullable();
            $table->json('items')->nullable(); // Store invoice items as JSON
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['client_id', 'status']);
            $table->index('status');
            $table->index('due_date');
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
