<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Quote Request -> Quotes (one per insurer plan) -> Order -> Payments -> Policy */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->string('product', 40)->default('motor');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->text('input');                           // encrypted:array — full normalised form input
            $table->string('status', 30)->default('QUOTE_REQUESTED');
            $table->json('insurer_results')->nullable();     // per-insurer status / timing / error
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained()->cascadeOnDelete();
            $table->string('insurer', 40);
            $table->string('plan_code', 60);
            $table->string('plan_name');
            $table->string('cover_type', 20);                // comprehensive | third_party
            $table->string('repair_type', 20)->nullable();   // agency | garage
            $table->decimal('sum_insured', 12, 2)->nullable();
            $table->decimal('premium', 12, 2);
            $table->decimal('vat', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('deductible', 12, 2)->nullable();
            $table->json('covers')->nullable();              // [{code,name,premium,type:mandatory|optional}]
            $table->string('insurer_ref')->nullable();       // insurer quote number
            $table->json('raw')->nullable();                 // insurer response fragment for this plan
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
            $table->index(['quote_request_id', 'insurer']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('quote_request_id')->constrained();
            $table->foreignId('quote_id')->constrained();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('insurer', 40);
            $table->string('state', 40)->default('QUOTE_SELECTED');
            $table->json('selected_covers')->nullable();
            $table->decimal('premium', 12, 2);
            $table->decimal('vat', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->string('request_id', 64)->nullable();   // X-REQUEST-ID / partner quote no. used with the insurer
            $table->string('insurer_quote_no')->nullable();
            $table->text('payment_url')->nullable();
            $table->date('policy_start')->nullable();
            $table->json('history')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('AED');
            $table->string('channel', 40)->default('insurer_hosted');
            $table->string('status', 20)->default('pending'); // pending | success | failed
            $table->string('reference')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });

        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('insurer', 40);
            $table->string('policy_no')->index();
            $table->string('status', 30);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->decimal('sum_insured', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->json('documents')->nullable();           // insurer doc ids
            $table->json('raw')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable');                  // quote_request, order, policy
            $table->string('type', 40);                      // eid_front, mulkiya_back, policy_schedule ...
            $table->string('insurer_code', 20)->nullable();  // e.g. DIN 103
            $table->string('disk', 20)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 80);
            $table->unsignedInteger('size');
            $table->string('status', 20)->default('uploaded');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('policies');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('quote_requests');
    }
};
