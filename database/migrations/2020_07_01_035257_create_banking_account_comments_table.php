<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankingAccount\Activation\Comment\Entity;

class CreateBankingAccountCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANKING_ACCOUNT_COMMENT, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::ADMIN_ID, Entity::ID_LENGTH);

            $table->char(Entity::USER_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->char(Entity::BANKING_ACCOUNT_ID, Entity::ID_LENGTH);

            $table->text(Entity::COMMENT);

            $table->text(Entity::NOTES);

            $table->char(Entity::SOURCE_TEAM_TYPE, 255);

            $table->char(Entity::SOURCE_TEAM, 255);

            $table->string(Entity::TYPE, 64);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::ADDED_AT);

            $table->index([Entity::BANKING_ACCOUNT_ID, Entity::ADDED_AT]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::BANKING_ACCOUNT_COMMENT);
    }
}
