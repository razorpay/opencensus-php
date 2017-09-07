<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Invoice\Entity as Invoice;
use RZP\Models\Merchant\Entity as Merchant;

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

            $table->char(Invoice::INVOICE_NUMBER, 16);

            $table->integer(Invoice::MONTH)
                  ->unsigned();

            $table->integer(Invoice::YEAR)
                  ->unsigned();

            $table->string(Invoice::GSTIN, 15)
                  ->nullable();

            $table->string(Invoice::TYPE, 20);

            $table->string(Invoice::DESCRIPTION)
                  ->nullable();

            $table->integer(Invoice::AMOUNT);

            $table->integer(Invoice::TAX);

            $table->integer(Invoice::AMOUNT_DUE)
                  ->unsigned()
                  ->default(0);

            $table->integer(Invoice::CREATED_AT);

            $table->integer(Invoice::UPDATED_AT);

            // Indices
            $table->index([Invoice::MERCHANT_ID, Invoice::YEAR, Invoice::MONTH]);

            $table->index(Invoice::INVOICE_NUMBER);

            $table->index(Invoice::GSTIN);

            $table->index(Invoice::CREATED_AT);

            $table->index(Invoice::UPDATED_AT);

            // Foreign Keys
            $table->foreign(Invoice::MERCHANT_ID)
                  ->references(Merchant::ID)
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
        Schema::table(Table::MERCHANT_INVOICE, function(Blueprint $table)
        {
            $table->dropForeign(Table::MERCHANT_INVOICE .'_' .Invoice::MERCHANT_ID .'_foreign');
        });

        Schema::drop(Table::MERCHANT_INVOICE);
    }
}
