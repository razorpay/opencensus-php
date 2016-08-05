<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Details\Entity;

class CreateSettlementDetailsTable extends Migration
{
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

            $table->char(Entity::SETTLEMENT_ID, 14);

            $table->string(Entity::TYPE);

            $table->string(Entity::TYPEXYZ);

            $table->integer(Entity::COUNT)
                  ->nullable();

            $table->integer(Entity::AMOUNT);

            $table->string(Entity::DESCRIPTION)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Entity::SETTLEMENT_ID)
                  ->references(Settlement\Entity::ID)
                  ->on(Table::SETTLEMENT)
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

            $table->dropForeign(
                Table::SETTLEMENT_DETAIL.'_',Entity::SETTLEMENT_ID.'_foreign');
        });

        Schema::drop(Table::SETTLEMENT_DETAIL);
    }
}