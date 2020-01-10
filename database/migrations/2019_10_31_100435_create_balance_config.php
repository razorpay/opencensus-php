<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\Balance\BalanceConfig\Entity;

class CreateBalanceConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BALANCE_CONFIG, function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::BALANCE_ID, \RZP\Models\Merchant\Balance\Entity::ID_LENGTH);

            $table->integer(Entity::NEGATIVE_LIMIT_AUTO)
                  ->unsigned()
                  ->nullable();

            $table->integer(Entity::NEGATIVE_LIMIT_MANUAL)
                ->unsigned()
                ->nullable();

            $table->json(Entity::NEGATIVE_TRANSACTION_FLOWS)
                  ->nullable();

            $table->char(Entity::TYPE)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::BALANCE_ID);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::TYPE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::drop(Table::BALANCE_CONFIG);
    }
}
