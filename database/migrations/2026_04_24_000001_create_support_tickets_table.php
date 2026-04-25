<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('support_tickets')) {
            return;
        }

        Schema::connection($this->connection)->create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('tenant_name');
            $table->unsignedBigInteger('requester_id')->nullable();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('subject');
            $table->string('category', 40)->default('general');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('open');
            $table->text('message');
            $table->text('superadmin_response')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('central_superadmins')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('support_tickets');
    }
};
