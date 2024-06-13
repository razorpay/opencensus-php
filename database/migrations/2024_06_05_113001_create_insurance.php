<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Table::INSURANCE, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char('id', 14)->primary();
            $table->char('insured_entity_id', 14)->collation('utf8mb3_bin');
            $table->string('insured_entity_type', 30)->collation('utf8mb3_bin');
            $table->char('merchant_id', 14)->collation('utf8mb4_bin');
            $table->string('status', 30);
            $table->string('claim_status', 30)->nullable();
            $table->string('insurance_ref_id', 30)->nullable();
            $table->string('insurance_provider', 30)->nullable();
            $table->bigInteger('expiry_timestamp')->nullable();
            $table->bigInteger('created_at')->nullable();
            $table->bigInteger('updated_at')->nullable();

            $table->unique(['insured_entity_type', 'insured_entity_id'], 'unique_insured_entity_type_insured_entity_id');
            $table->index('status', 'idx_status');
            $table->index('claim_status', 'idx_claim_status');
            $table->index('created_at', 'idx_created_at');
            $table->index('updated_at', 'idx_updated_at');
            $table->index('merchant_id', 'idx_merchant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance');
    }
};
