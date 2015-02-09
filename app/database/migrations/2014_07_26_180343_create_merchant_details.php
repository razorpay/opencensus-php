<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMerchantDetails extends Migration
{

    /**
    * Run the migrations.
    *
    * @return void
    */
    public function up()
    {
        Schema::create('merchant_details', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('merchant_id', 14)->primary();

            $table->string('contact_name')->nullable();

            $table->string('contact_email')->nullable();

            $table->string('contact_mobile')->nullable();

            $table->string('contact_landline')->nullable();

            $table->string('business_type')->nullable();

            $table->string('business_name')->nullable();

            $table->string('business_dba')->nullable();

            $table->string('business_website')->nullable();

            $table->boolean('business_international')->default(0);

            $table->string('business_paymentdetails')->nullable();

            $table->string('business_registered_address')->nullable();

            $table->string('business_registered_state')->nullable();

            $table->string('business_registered_city')->nullable();

            $table->string('business_registered_pin')->nullable();

            $table->string('business_operation_address')->nullable();

            $table->string('business_operation_state')->nullable();

            $table->string('business_operation_city')->nullable();

            $table->string('business_operation_pin')->nullable();

            $table->string('business_doe')->nullable();

            $table->string('company_cin')->nullable();

            $table->string('company_pan')->nullable();

            $table->string('company_pan_name')->nullable();

            $table->string('business_model')->nullable();

            $table->integer('transaction_volume')->unsigned();

            $table->integer('transaction_value')->unsigned();

            $table->string('promoter_pan')->nullable();

            $table->string('promoter_pan_name')->nullable();

            $table->string('bank_name')->nullable();

            $table->string('bank_account_number')->nullable();

            $table->string('bank_account_name')->nullable();

            $table->string('bank_account_type')->nullable();

            $table->string('bank_branch')->nullable();

            $table->string('bank_branch_ifsc')->nullable();

            $table->string('business_proof_url')->nullable();

            $table->string('business_pan_url')->nullable();

            $table->string('promoter_pan_url')->nullable();

            $table->string('address_proof_url')->nullable();

            $table->string('steps_finished')->nullable()->default('[]');

            $table->boolean('submitted')->default(0);

            $table->boolean('locked')->default(0);

            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('merchant_id')
                  ->references('id')
                  ->on('merchants');
        });
    }

    /**
    * Reverse the migrations.
    *
    * @return void
    */
    public function down()
    {
        Schema::table('merchant_details', function(Blueprint $table)
        {
            $table->dropForeign('merchant_details_merchant_id_foreign');
        });

        Schema::drop('merchant_details');
    }

}
