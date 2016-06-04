<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Merchant;
use Models\Customer;
use Models\Customer\App\Entity as App;
use Models\Payment\Entity as Payment;

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

            $table->char(App::DEVICE_TOKEN, 14);

            $table->integer(App::CREATED_AT);

            $table->integer(App::UPDATED_AT);

            $table->index(App::CREATED_AT);

            $table->foreign(App::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(App::CUSTOMER_ID)
                  ->references(Customer\Entity::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');
        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->foreign(Payment::APP_TOKEN)
                  ->references(App::ID)
                  ->on(Table::CUSTOMER_APP)
                  ->on_delete('restrict');
        });
	 }

  	/**
  	 * Reverse the migrations.
  	 *
  	 * @return void
  	 */
  	public function down()
  	{
        Schema::table(Table::PAYMENT, function($table)
        {
            $table->dropForeign(Table::PAYMENT.'_'.Payment::APP_TOKEN.'_foreign');
        });

        Schema::table(Table::CUSTOMER_APP, function($table)
        {
            $table->dropForeign(Table::CUSTOMER_APP.'_'.App::CUSTOMER_ID.'_foreign');

            $table->dropForeign(Table::CUSTOMER_APP.'_'.App::MERCHANT_ID.'_foreign');
        });

    		Schema::drop(Table::CUSTOMER_APP);
  	}
}