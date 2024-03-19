<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_business_details', function (Blueprint $table) {
            $table->char('id', 14)->charset('utf8mb4')->collation('utf8mb4_bin')->primary();
            $table->char('merchant_id', 14)->charset('utf8mb4')->collation('utf8mb4_bin');
            $table->json('website_details')->nullable();
            $table->integer('created_at');
            $table->integer('updated_at');
            $table->json('app_urls')->nullable();
            $table->json('gst_details')->nullable();
            $table->string('business_parent_category', 255)->nullable();
            $table->char('audit_id', 14)->nullable()->charset('utf8mb4')->collation('utf8mb4_bin');
            $table->string('blacklisted_products_category', 255)->nullable();
            $table->json('plugin_details')->nullable();
            $table->string('onboarding_source', 255)->nullable();
            $table->json('lead_score_components')->nullable();
            $table->string('pg_use_case', 500)->nullable();
            $table->unsignedInteger('miq_sharing_date')->default(0);
            $table->unsignedInteger('testing_credentials_date')->default(0);
            $table->json('metadata')->nullable();

            // Indexes
            $table->index('merchant_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_business_details');
    }
};
