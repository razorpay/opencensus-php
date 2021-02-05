<?php

use RZP\Constants\Table;
use RZP\Models\Merchant\Balance;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Partner\Commission\Invoice\Entity;

class CreateCommissionInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::COMMISSION_INVOICE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->integer(Entity::MONTH)
                  ->unsigned();

            $table->integer(Entity::YEAR)
                  ->unsigned();

            $table->string(Entity::STATUS, 32);

            $table->integer(Entity::GROSS_AMOUNT)
                  ->unsigned();

            $table->integer(Entity::TAX_AMOUNT)
                  ->unsigned();

            $table->char(Entity::BALANCE_ID, Balance\Entity::ID_LENGTH);

            $table->text(Entity::NOTES)
                  ->nullable();

            $table->text(Entity::TNC)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::MERCHANT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::COMMISSION_INVOICE);
    }
}
