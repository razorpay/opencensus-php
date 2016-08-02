<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBenefeciaryColumnsToMerchantDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('merchant_details', function($table)
        {
            $table->string('bank_beneficiary_city')->nullable();
            $table->string('bank_beneficiary_state')->nullable();
            $table->string('bank_beneficiary_pin')->nullable();
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
            $table->dropColumn('bank_beneficiary_city');
            $table->dropColumn('bank_beneficiary_state');
            $table->dropColumn('bank_beneficiary_pin');
        });
	}

}
