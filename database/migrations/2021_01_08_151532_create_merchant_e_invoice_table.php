<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use RZP\Models\Merchant\Entity as Merchant;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\Invoice\EInvoice\Entity as EInvoice;

class CreateMerchantEInvoiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_E_INVOICE, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(EInvoice::ID, EInvoice::ID_LENGTH)
                ->primary();

            $table->string(EInvoice::INVOICE_NUMBER);

            $table->char(EInvoice::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->integer(EInvoice::MONTH)
                ->unsigned();

            $table->integer(EInvoice::YEAR)
                ->unsigned();

            $table->string(EInvoice::TYPE, EInvoice::TYPE_LENGTH);

            $table->char(EInvoice::DOCUMENT_TYPE, EInvoice::DOCUMENT_TYPE_LENGTH);

            $table->string(EInvoice::GSTIN, 15);

            $table->string(EInvoice::STATUS, 20);

            $table->string(EInvoice::GSP_STATUS, 10)
                ->default(null)
                ->nullable();

            $table->text(EInvoice::GSP_ERROR)
                ->nullable();

            $table->text(EInvoice::RZP_ERROR)
                ->nullable();

            $table->string(EInvoice::GSP_IRN)
                ->default(null)
                ->nullable();

            $table->text(EInvoice::GSP_SIGNED_INVOICE)
                ->nullable();

            $table->text(EInvoice::GSP_SIGNED_QR_CODE)
                ->nullable();

            $table->text(EInvoice::GSP_QR_CODE_URL)
                ->nullable();

            $table->text(EInvoice::GSP_E_INVOICE_PDF)
                ->nullable();

            $table->integer(EInvoice::ATTEMPTS)
                ->default(0)
                ->unsigned();

            $table->integer(EInvoice::CREATED_AT);

            $table->integer(EInvoice::UPDATED_AT);

            // Indices
            $table->index([EInvoice::MERCHANT_ID, EInvoice::YEAR, EInvoice::MONTH]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_E_INVOICE);
    }
}
