<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Transfer\Entity;
use RZP\Constants\Table;
use RZP\Models\Transaction;

class CreateTransfers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TRANSFER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::FROM, 50);
            $table->char(Entity::FROM_ID, Entity::ID_LENGTH);

            $table->string(Entity::TO, 50);
            $table->char(Entity::TO_ID, Entity::ID_LENGTH);

            $table->integer(Entity::AMOUNT);

            $table->char(Entity::TRANSACTION_ID, Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);

            $table->foreign(Entity::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
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
        Schema::table(Table::TRANSFER, function($table)
        {
            $table->dropForeign
            (
                Table::TRANSFER . '_' . Entity::TRANSACTION_ID . '_foreign'
            );
        });

        Schema::drop(Table::TRANSFER);
    }
}
