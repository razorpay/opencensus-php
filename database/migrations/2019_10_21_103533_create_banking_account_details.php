<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankingAccount\Detail\Entity;

class CreateBankingAccountDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANKING_ACCOUNT_DETAIL, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::GATEWAY_VALUE, 255);

            $table->char(Entity::GATEWAY_KEY, 255);

            $table->char(Entity::BANKING_ACCOUNT_ID, Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);
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
    }
}
