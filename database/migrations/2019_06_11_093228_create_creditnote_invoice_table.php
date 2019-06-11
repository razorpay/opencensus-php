<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\CreditNote\Invoice\Entity;

class CreateCreditNoteInvoiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CREDITNOTE_INVOICE, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->char(Entity::CREDITNOTE_ID, Entity::ID_LENGTH);

            $table->char(Entity::INVOICE_ID, Entity::ID_LENGTH);

            $table->char(Entity::REFUND_ID, Entity::ID_LENGTH);

            $table->char(Entity::STATUS, 16);

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::CREDITNOTE_ID);

            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::CREDITNOTE_INVOICE);
    }
}
