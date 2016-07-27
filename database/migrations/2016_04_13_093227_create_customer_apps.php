<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Models\Customer\AppToken\Entity as AppToken;
use RZP\Models\Payment\Entity as Payment;

class CreateCustomerApps extends Migration {
    /**
     * Run the migrations.
  	 *
  	 * @return void
  	 */
  	public function up()
  	{
        Schema::create(Table::APP_TOKEN, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(AppToken::ID, 14)
                  ->primary();

            $table->char(AppToken::MERCHANT_ID, 14);

            $table->char(AppToken::CUSTOMER_ID, 14);

            $table->char(AppToken::DEVICE_TOKEN, 14);

            $table->integer(AppToken::CREATED_AT);

            $table->integer(AppToken::UPDATED_AT);

            $table->integer(AppToken::DELETED_AT)
                  ->nullable();

            $table->index(AppToken::CREATED_AT);

            $table->foreign(AppToken::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(AppToken::CUSTOMER_ID)
                  ->references(Customer\Entity::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');
        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->foreign(Payment::APP_TOKEN)
                  ->references(AppToken::ID)
                  ->on(Table::APP_TOKEN)
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

        Schema::table(Table::APP_TOKEN, function($table)
        {
            $table->dropForeign(Table::APP_TOKEN.'_'.AppToken::CUSTOMER_ID.'_foreign');

            $table->dropForeign(Table::APP_TOKEN.'_'.AppToken::MERCHANT_ID.'_foreign');
        });

		Schema::drop(Table::APP_TOKEN);
  	}
}
