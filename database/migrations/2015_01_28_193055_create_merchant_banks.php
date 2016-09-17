<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Methods\Entity as Methods;

class CreateMerchantBanks extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::METHODS, function(Blueprint $table)
        {
            $table->char(Methods::MERCHANT_ID, Methods::ID_LENGTH)
                  ->primary();

            $table->boolean(Methods::CARD)
                  ->default(1);

            $table->boolean(Methods::NETBANKING)
                  ->default(1);

            $table->boolean(Methods::AMEX)
                  ->default(0);

            $table->text(Methods::BANKS);

            $table->boolean(Methods::PAYTM)
                  ->default(0);

            $table->boolean(Methods::MOBIKWIK)
                  ->default(0);

            $table->boolean(Methods::OLAMONEY)
                  ->default(0);

            $table->boolean(Methods::PAYZAPP)
                  ->default(0);

            $table->boolean(Methods::PAYUMONEY)
                  ->default(0);

            $table->boolean(Methods::AIRTELMONEY)
                  ->default(0);

            $table->boolean(Methods::EMI)
                  ->default(0);

            $table->boolean(Methods::CREDIT_CARD)
                  ->default(1);

            $table->boolean(Methods::DEBIT_CARD)
                  ->default(1);

            $table->integer(Methods::CREATED_AT);

            $table->integer(Methods::UPDATED_AT);

            $table->foreign(Methods::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index(Methods::CARD);

            $table->index(Methods::PAYTM);

            $table->index(Methods::MOBIKWIK);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::METHODS, function($table)
        {
            $table->dropForeign(Table::METHODS.'_'.Methods::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::METHODS);
    }
}
