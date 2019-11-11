<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Invoice\Entity as Invoice;
use RZP\Models\Merchant\Balance\Entity as Balance;

class CreateMerchantInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_INVOICE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Invoice::ID, Invoice::ID_LENGTH)
                  ->primary();

            $table->char(Invoice::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->string(Invoice::INVOICE_NUMBER, Invoice::INVOICE_NUMBER_LENGTH);

            $table->integer(Invoice::MONTH)
                  ->unsigned();

            $table->integer(Invoice::YEAR)
                  ->unsigned();

            $table->string(Invoice::GSTIN, 15)
                  ->nullable();

            $table->string(Invoice::TYPE, 20);

            $table->string(Invoice::DESCRIPTION)
                  ->nullable();

            $table->bigInteger(Invoice::AMOUNT);

            $table->integer(Invoice::TAX);

            $table->bigInteger(Invoice::AMOUNT_DUE)
                  ->default(0);

            $table->char(Invoice::BALANCE_ID, Invoice::ID_LENGTH)
                  ->nullable();

            $table->integer(Invoice::CREATED_AT);

            $table->integer(Invoice::UPDATED_AT);

            // Indices
            $table->index([Invoice::MERCHANT_ID, Invoice::YEAR, Invoice::MONTH]);

            $table->index(Invoice::INVOICE_NUMBER);

            $table->index(Invoice::GSTIN);

            $table->index(Invoice::BALANCE_ID);

            $table->index(Invoice::CREATED_AT);

            $table->index(Invoice::UPDATED_AT);

            // Foreign Keys
            $table->foreign(Invoice::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Invoice::BALANCE_ID)
                  ->references(Balance::ID)
                  ->on(Table::BALANCE)
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
        Schema::table(Table::MERCHANT_INVOICE, function(Blueprint $table)
        {
            $table->dropForeign(Table::MERCHANT_INVOICE . '_' . Invoice::MERCHANT_ID . '_foreign');

            $table->dropForeign(Table::MERCHANT_INVOICE . '_' . Invoice::BALANCE_ID . '_foreign');
        });

        Schema::drop(Table::MERCHANT_INVOICE);
    }
}
