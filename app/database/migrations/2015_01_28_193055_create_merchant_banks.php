<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Merchant;
use Models\Merchant\Banks\Entity as MerchantBanks;

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
            $table->char(MerchantBanks::MERCHANT_ID, MerchantBanks::ID_LENGTH)
                  ->primary();

            $table->boolean(MerchantBanks::CARD)
                  ->default(1);

            $table->text(MerchantBanks::BANKS);

            $table->boolean(MerchantBanks::PAYTM)
                  ->default(0);

            $table->integer(MerchantBanks::CREATED_AT);

            $table->integer(MerchantBanks::UPDATED_AT);

            $table->foreign(MerchantBanks::MERCHANT_ID)
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
            $table->dropForeign(Table::MERCHANT_BANKS.'_'.MerchantBanks::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::MERCHANT_BANKS);
    }
}
