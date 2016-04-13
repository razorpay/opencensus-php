<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Merchant;
use Models\Customer;
use Models\Customer\App\Entity as App;

class CreateCustomerApps extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create(Table::CUSTOMER_APP, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(App::ID, 14)
                  ->primary();

            $table->char(App::MERCHANT_ID, 14);

            $table->char(App::CUSTOMER_ID, 14);

            $table->char(App::APP_ID, 14);

            $table->string(App::DEVICE_ID, 50);

            $table->integer(App::CREATED_AT);

            $table->integer(App::UPDATED_AT);

            $table->index(App::CREATED_AT);

            $table->index(App::APP_ID);
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop(Table::CUSTOMER_APP);
	}
}