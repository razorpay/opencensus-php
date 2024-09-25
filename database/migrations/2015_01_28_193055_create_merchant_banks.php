<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Card\Network;
use RZP\Models\Emi\DebitProvider;
use RZP\Models\Merchant\Methods\Entity as Methods;
use RZP\Models\Payment\Processor\App as AppMethod;

class CreateMerchantBanks extends Migration
{
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

            $table->tinyInteger(Methods::CARD)
                  ->default(1);

            $table->tinyInteger(Methods::NETBANKING)
                  ->default(1);

            $table->tinyInteger(Methods::AMEX)
                  ->default(0);

            $table->text(Methods::DISABLED_BANKS)
                  ->nullable();

            $table->text(Methods::BANKS);

            $table->tinyInteger(Methods::PAYTM)
                  ->default(0);

            $table->tinyInteger(Methods::MOBIKWIK)
                  ->default(0);

            $table->tinyInteger(Methods::OLAMONEY)
                  ->default(0);

            $table->tinyInteger(Methods::PHONEPE)
                ->default(0);

            $table->tinyInteger(Methods::PAYPAL)
                ->default(0);

            $table->tinyInteger(Methods::PHONEPE_SWITCH)
                  ->default(0);

            $table->tinyInteger(Methods::PAYZAPP)
                  ->default(0);

            $table->tinyInteger(Methods::PAYUMONEY)
                  ->default(0);

            $table->tinyInteger(Methods::OPENWALLET)
                  ->default(0);

            $table->tinyInteger(Methods::RAZORPAYWALLET)
                  ->default(0);

            $table->tinyInteger(Methods::AIRTELMONEY)
                  ->default(0);

            $table->boolean(Methods::AMAZONPAY)
                  ->default(0);

            $table->tinyInteger(Methods::JIOMONEY)
                  ->default(0);

            $table->tinyInteger(Methods::SBIBUDDY)
                  ->default(0);

            $table->tinyInteger(Methods::MPESA)
                  ->default(0);

            $table->tinyInteger(Methods::EMI)
                  ->default(0);

            $table->boolean(Methods::FREECHARGE)
                  ->default(0);

            $table->tinyInteger(Methods::CREDIT_CARD)
                  ->default(1);

            $table->tinyInteger(Methods::DEBIT_CARD)
                  ->default(1);

            $table->integer(Methods::CARD_SUBTYPE)
                  ->unsigned()
                  ->default(1);

            $table->tinyInteger(Methods::PREPAID_CARD)
                  ->default(1);

            $table->tinyInteger(Methods::UPI)
                  ->default(0);

            $table->tinyInteger(Methods::UPI_TYPE)
                  ->default(3);

            $table->tinyInteger(Methods::BANK_TRANSFER)
                  ->default(0);

            $table->tinyInteger(Methods::AEPS)
                  ->default(0);

            $table->tinyInteger(Methods::EMANDATE)
                  ->default(0);

            $table->tinyInteger(Methods::NACH)
                  ->default(0);

            $table->tinyInteger(Methods::CARDLESS_EMI)
                  ->default(0);

            $table->tinyInteger(Methods::PAYLATER)
                  ->default(0);

            $table->unsignedSmallInteger(Methods::CARD_NETWORKS)
                  ->default(Network::DEFAULT_CARD_NETWORKS);

            $table->unsignedSmallInteger(Methods::APPS)
                  ->default(AppMethod::DEFAULT_APPS);

            $table->unsignedSmallInteger(Methods::DEBIT_EMI_PROVIDERS)
                  ->nullable();

            $table->json(Methods::ADDITIONAL_WALLETS)
                  ->nullable();

            $table->tinyInteger(Methods::COD)
                  ->default(0);

            $table->tinyInteger(Methods::OFFLINE)
                  ->default(0);

            $table->tinyInteger(Methods::FPX)
                  ->default(0);

            $table->json(Methods::ADDON_METHODS)
                ->nullable();

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
