<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Invoice;
use RZP\Models\Invoice\InvoiceItem\Entity;
use RZP\Models\Item;

class CreateInvoiceItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::INVOICE_ITEM, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::INVOICE_ID, Invoice\Entity::ID_LENGTH);

            $table->char(Entity::ITEM_ID, Item\Entity::ID_LENGTH);

            // $table->integer(Entity::QUANTITY);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::INVOICE_ID);
            $table->index(Entity::ITEM_ID);

            $table->foreign(Entity::INVOICE_ID)
                ->references(Invoice\Entity::ID)
                ->on(Table::INVOICE)
                ->on_delete('restrict');

            $table->foreign(Entity::ITEM_ID)
                  ->references(Item\Entity::ID)
                  ->on(Table::ITEM)
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
        Schema::table(Table::INVOICE_ITEM, function($table)
        {
            $table->dropForeign
            (
                Table::INVOICE_ITEM . '_' . Entity::INVOICE_ID . '_foreign'
            );
        });

        Schema::table(Table::INVOICE_ITEM, function($table)
        {
            $table->dropForeign
            (
                Table::INVOICE_ITEM . '_' . Entity::ITEM_ID . '_foreign'
            );
        });

        Schema::drop(Table::INVOICE_ITEM);
    }
}