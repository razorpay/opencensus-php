<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Models\Settlement\Detail;

class CreateSettlementDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create(Table::SETTLEMENT_DETAIL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, 14)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, 14);

	        $table->integer(Entity::PAYMENT_COUNT)
	     		  ->default(0);

	        $table->integer(Entity::PAYMENT_AMOUNT)
	     		  ->default(0);

	        $table->integer(Entity::REFUND_COUNT)
	     		  ->default(0);

	        $table->integer(Entity::REFUND_AMOUNT)
	     		  ->default(0);

	        $table->integer(Entity::ADJUSTMENT_COUNT)
	     		  ->default(0);

	        $table->integer(Entity::ADJUSTMENT_AMOUNT)
	     		  ->default(0);

	        $table->integer(Entity::TOTAL_AMOUNT)
	     		  ->default(0);

	        $table->integer(Entity::PLAN_FEE)
	     		  ->default(0);

	        $table->integer(Entity::SERVICE_TAX)
	     		  ->default(0);

	        $table->integer(Entity::TOTAL_FEE)
	     		  ->default(0);

	        $table->integer(Entity::SETTLEMENT_AMOUNT)
	     		  ->default(0);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
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
        Schema::table(Table::SETTLEMENT_DETAIL, function($table)
        {
            $table->dropForeign(
                Table::SETTLEMENT_DETAIL.'_'.Entity::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::SETTLEMENT_DETAIL);
    }
}