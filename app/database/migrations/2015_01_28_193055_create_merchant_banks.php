<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Merchant;
use Models\Merchant\Methods\Entity as Methods;

class CreateMerchantBanks extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_BANKS, function(Blueprint $table)
        {
            $table->char(Methods::MERCHANT_ID, Methods::ID_LENGTH)
                  ->primary();

            $table->boolean(Methods::CARD)
                  ->default(1);

            $table->text(Methods::BANKS);

            $table->boolean(Methods::PAYTM)
                  ->default(0);

            $table->integer(Methods::CREATED_AT);

            $table->integer(Methods::UPDATED_AT);

            $table->foreign(Methods::MERCHANT_ID)
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
        Schema::table(Table::MERCHANT_BANKS, function($table)
        {
            $table->dropForeign(Table::MERCHANT_BANKS.'_'.Methods::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::MERCHANT_BANKS);
    }
}
