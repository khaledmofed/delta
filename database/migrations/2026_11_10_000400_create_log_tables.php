<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every call to an insurer API (secrets and document content redacted).
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('insurer', 40)->index();
            $table->string('operation', 60);
            $table->string('request_id', 64)->nullable()->index();
            $table->string('method', 10);
            $table->string('url');
            $table->json('request')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('insurer_status', 40)->nullable();
            $table->json('response')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error')->nullable();
            $table->nullableMorphs('subject');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('actor')->nullable();
            $table->string('action', 80);
            $table->nullableMorphs('subject');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('api_logs');
    }
};
