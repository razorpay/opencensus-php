<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\LineItem\Entity;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Constants\Table;

class CreateLineItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::LINE_ITEM, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            // This is nullable because the association happens after
            // creating a line item.
            $table->char(Entity::INVOICE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::NAME, 512);

            $table->string(Entity::DESCRIPTION, 2048)
                  ->nullable();

            $table->integer(Entity::AMOUNT);

            $table->integer(Entity::QUANTITY);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::AMOUNT);
            //$table->index(Entity::LISTING_ID);

            $table->foreign(Entity::MERCHANT_ID)
                ->references(Merchant\Entity::ID)
                ->on(Table::MERCHANT)
                ->on_delete('restrict');

            $table->foreign(Entity::INVOICE_ID)
                  ->references(Invoice\Entity::ID)
                  ->on(Table::INVOICE)
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
        Schema::table(Table::LINE_ITEM, function($table)
        {
            $table->dropForeign
            (
                Table::LINE_ITEM . '_' . Entity::MERCHANT_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::LINE_ITEM . '_' . Entity::INVOICE_ID . '_foreign'
            );
        });

        Schema::drop(Table::LINE_ITEM);
    }
}
