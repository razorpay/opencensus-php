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

            $table->char('merchant_id', 24)->primary();

            $table->string('contact_name')->nullable();

            $table->string('contact_email')->nullable();

            $table->string('contact_mobile')->nullable();

            $table->string('contact_landline')->nullable();

            $table->string('bussiness_type')->nullable();

            $table->string('bussiness_category')->nullable();

            $table->string('bussiness_subcategory')->nullable();

            $table->string('bussiness_registered_address')->nullable();

            $table->string('bussiness_registered_state')->nullable();

            $table->string('bussiness_registered_city')->nullable();

            $table->string('bussiness_registered_pin')->nullable();

            $table->string('bussiness_operation_address')->nullable();

            $table->string('bussiness_operation_state')->nullable();

            $table->string('bussiness_operation_city')->nullable();

            $table->string('bussiness_operation_pin')->nullable();

            $table->string('bussiness_doe')->nullable();

            $table->string('company_cin')->nullable();

            $table->string('company_pan')->nullable();

            $table->string('company_pan_name')->nullable();

            $table->string('bussiness_model')->nullable();

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

            $table->string('bussiness_proof_url')->nullable();

            $table->string('bussiness_pan_url')->nullable();

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
