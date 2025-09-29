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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('domain')->unique();
            $table->string('registrar');
            $table->enum('status', ['Active', 'Expired', 'Pending', 'Cancelled'])->default('Pending');
            $table->date('registration_date');
            $table->date('expiry_date');
            $table->boolean('auto_renew')->default(true);
            $table->json('nameservers')->nullable(); // Store nameservers as JSON array
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['client_id', 'status']);
            $table->index('status');
            $table->index('expiry_date');
            $table->index('domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
