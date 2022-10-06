<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;
use RZP\Models\Reversal\Entity;
use RZP\Models\Base\PublicEntity;

class CreateReversals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::REVERSAL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->string(Entity::CUSTOMER_ID, Customer\Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::ENTITY_ID, PublicEntity::ID_LENGTH);

            $table->char(Entity::ENTITY_TYPE, 255);

            // Todo: Remove null-able after code deploy and backfilling
            $table->string(Entity::BALANCE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Entity::AMOUNT)
                  ->unsigned();

            $table->integer(Entity::FEE)
                  ->unsigned()
                  ->default(0);

            $table->integer(Entity::TAX)
                  ->unsigned()
                  ->default(0);

            $table->char(Entity::CURRENCY, 3);

            // TODO: Remove null-able after code deploy and backfilling
            $table->string(Entity::CHANNEL, 255)
                  ->nullable();

            // TODO: Figure out uniqueness stuff
            $table->string(Entity::UTR)
                  ->nullable();

            $table->text(Entity::NOTES);

            $table->char(Entity::TRANSACTION_ID, Transaction\Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::TRANSACTION_TYPE, 255)
                  ->nullable();

            $table->string(Entity::INITIATOR_ID, Merchant\Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::CUSTOMER_REFUND_ID, Refund\Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::ENTITY_ID);

            $table->index(Entity::UTR);

            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::REVERSAL, function($table)
        {
            $table->dropForeign(Table::REVERSAL . '_' . Entity::MERCHANT_ID . '_foreign');

            $table->dropForeign(Table::REVERSAL . '_' . Entity::CUSTOMER_ID . '_foreign');
        });

        Schema::drop(Table::REVERSAL);
    }
}
