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

            $table->string('contact_name');

            $table->string('contact_email');

            $table->string('contact_mobile');

            $table->string('contact_landline');

            $table->string('bussiness_type');

            $table->string('bussiness_category');

            $table->string('bussiness_subcategory');

            $table->string('bussiness_registered_address');

            $table->string('bussiness_registered_state');

            $table->string('bussiness_registered_city');

            $table->string('bussiness_registered_pin');

            $table->string('bussiness_operation_address');

            $table->string('bussiness_operation_state');

            $table->string('bussiness_operation_city');

            $table->string('bussiness_operation_pin');

            $table->string('bussiness_doe');

            $table->string('company_cin');

            $table->string('company_pan');

            $table->string('company_pan_name');

            $table->string('bussiness_model');

            $table->integer('transaction_volume')->unsigned();

            $table->integer('transaction_value')->unsigned();

            $table->string('promoter_pan');

            $table->string('promoter_pan_name');

            $table->string('bank_name');

            $table->string('bank_account_number');

            $table->string('bank_account_name');

            $table->string('bank_account_type');

            $table->string('bank_branch');

            $table->string('bank_branch_ifsc');

            $table->string('bussiness_proof_url');

            $table->string('bussiness_pan_url');

            $table->string('promoter_pan_url');

            $table->string('address_proof_url');

            $table->string('steps_finished')->default('[]');

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
