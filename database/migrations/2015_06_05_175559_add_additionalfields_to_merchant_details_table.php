<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdditionalfieldsToMerchantDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('merchant_details', function($table)
        {
            $table->string('website_about')->nullable();
            $table->string('website_contact')->nullable();
            $table->string('website_privacy')->nullable();
            $table->string('website_terms')->nullable();
            $table->string('website_refund')->nullable();
            $table->string('website_pricing')->nullable();
            $table->string('website_login')->nullable();
            $table->string('business_operation_proof_url')->nullable();
            $table->string('promoter_proof_url')->nullable();
            $table->string('promoter_address_url')->nullable();

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('merchant_details', function($table)
        {
        	$table->dropColumn('website_about');
            $table->dropColumn('website_contact');
            $table->dropColumn('website_privacy');
            $table->dropColumn('website_terms');
            $table->dropColumn('website_refund');
            $table->dropColumn('website_pricing');
            $table->dropColumn('website_login');
            $table->dropColumn('business_operation_proof_url');
            $table->dropColumn('promoter_proof_url');
            $table->dropColumn('promoter_address_url');
        });
	}

}
