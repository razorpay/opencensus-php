<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Item;
use RZP\Models\Merchant;
use RZP\Models\Plan\Subscription\Addon\Entity;
use RZP\Models\Invoice;
use RZP\Models\LineItem;
use RZP\Constants\Table;
use RZP\Models\Plan\Subscription;

class CreateAddOns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADDON, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::SUBSCRIPTION_ID, Entity::ID_LENGTH);

            $table->char(Entity::ITEM_ID, Entity::ID_LENGTH);

            $table->char(Entity::INVOICE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Entity::QUANTITY)
                  ->default(1);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::DELETED_AT);
            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Entity::ITEM_ID)
                  ->references(Item\Entity::ID)
                  ->on(Table::ITEM)
                  ->on_delete('restrict');

            $table->foreign(Entity::SUBSCRIPTION_ID)
                  ->references(Subscription\Entity::ID)
                  ->on(Table::SUBSCRIPTION)
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
        Schema::table(Table::ADDON, function($table)
        {
            $table->dropForeign
            (
                Table::ADDON . '_' . Entity::MERCHANT_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::ADDON . '_' . Entity::ITEM_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::ADDON . '_' . Entity::SUBSCRIPTION_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::ADDON . '_' . Entity::INVOICE_ID . '_foreign'
            );
        });

        Schema::drop(Table::ADDON);
    }
}
