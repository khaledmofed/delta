<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Product-agnostic catalogue: Product -> Capabilities -> Providers (insurers). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();          // motor, medical, home, travel, boat, pet
            $table->string('name');
            $table->string('status', 20)->default('active'); // active | inactive
            $table->json('capabilities')->nullable();       // online_quote, online_purchase, lead_form, ...
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();           // din, awnic (matches config/insurers.php)
            $table->string('name');
            $table->string('environment', 20)->default('uat'); // uat | production
            $table->boolean('enabled')->default(false);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->string('logo')->nullable();
            $table->timestamps();
        });

        Schema::create('insurer_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->unique(['insurer_id', 'product_id']);
        });

        // Reference / master data per insurer (nationality, emirate, plate code, make, model, ...)
        Schema::create('insurer_codes', function (Blueprint $table) {
            $table->id();
            $table->string('insurer', 40);
            $table->string('type', 60);
            $table->string('code', 60);
            $table->string('label');
            $table->string('parent', 60)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['insurer', 'type', 'code', 'parent']);
            $table->index(['insurer', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurer_codes');
        Schema::dropIfExists('insurer_product');
        Schema::dropIfExists('insurers');
        Schema::dropIfExists('products');
    }
};
