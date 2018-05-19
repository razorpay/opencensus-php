<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\PaymentLink\Entity;
use RZP\Models\PaymentLink\Status;
use RZP\Models\PaymentLink\StatusReason;

class CreatePaymentLinks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENT_LINK, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::RECEIPT, 40)
                  ->nullable();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->bigInteger(Entity::AMOUNT)
                  ->nullable();

            $table->string(Entity::CURRENCY, 3);

            $table->integer(Entity::EXPIRE_BY)
                  ->nullable();

            $table->bigInteger(Entity::TIMES_PAYABLE)
                  ->nullable();

            $table->bigInteger(Entity::TIMES_PAID)
                  ->default(0);

            $table->bigInteger(Entity::TOTAL_AMOUNT)
                  ->default(0);

            $table->string(Entity::STATUS, 255);

            $table->string(Entity::STATUS_REASON, 255)
                  ->nullable();

            $table->string(Entity::SHORT_URL, 255)
                  ->nullable();

            $table->char(Entity::USER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->text(Entity::TITLE)
                  ->nullable();

            $table->text(Entity::DESCRIPTION)
                  ->nullable();

            $table->text(Entity::NOTES)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::RECEIPT);
            $table->index(Entity::EXPIRE_BY);
            $table->index(Entity::USER_ID);
            $table->index([Entity::STATUS, Entity::STATUS_REASON]);
            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
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
        Schema::table(Table::PAYMENT_LINK, function($table)
        {
            $table->dropForeign
            (
                Table::PAYMENT_LINK . '_' . Entity::MERCHANT_ID . '_foreign'
            );
        });

        Schema::drop(Table::PAYMENT_LINK);
    }
}
