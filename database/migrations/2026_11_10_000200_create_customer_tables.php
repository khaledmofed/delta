<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('email')->index();
            $table->string('mobile', 20);
            $table->text('emirates_id')->nullable();        // encrypted cast
            $table->date('emirates_id_expiry')->nullable();
            $table->date('dob')->nullable();
            $table->char('gender', 1)->nullable();
            $table->string('nationality', 10)->nullable();   // DIN nationality code
            $table->string('emirate', 10)->nullable();
            $table->text('license_no')->nullable();          // encrypted cast
            $table->date('license_from')->nullable();
            $table->date('license_to')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('chassis', 30)->index();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('trim')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->decimal('value', 12, 2)->nullable();
            $table->string('tcf_number', 30)->nullable();
            $table->string('plate_source', 10)->nullable();
            $table->string('plate_code', 10)->nullable();
            $table->string('plate_number', 20)->nullable();
            $table->date('registration_date')->nullable();
            $table->boolean('is_new')->default(false);
            $table->boolean('is_gcc')->default(true);
            $table->string('finance_bank', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->string('product', 40);
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile', 20);
            $table->json('data')->nullable();
            $table->string('status', 20)->default('new');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('next_action_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('customers');
    }
};
