<?php

use RZP\Models\Base\UniqueIdEntity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankingAccount\Activation\CallLog\Entity;

class CreateBankingAccountCallLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANKING_ACCOUNT_CALL_LOG, function (Blueprint $table) {
            $table->char(UniqueIdEntity::ID, UniqueIdEntity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::ADMIN_ID, UniqueIdEntity::ID_LENGTH);

            $table->char(Entity::COMMENT_ID, UniqueIdEntity::ID_LENGTH);

            $table->char(Entity::BANKING_ACCOUNT_ID, UniqueIdEntity::ID_LENGTH);

            $table->char(Entity::STATE_LOG_ID, UniqueIdEntity::ID_LENGTH);

            $table->integer(Entity::DATE_AND_TIME);

            $table->integer(Entity::FOLLOW_UP_DATE_AND_TIME);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index([Entity::BANKING_ACCOUNT_ID]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::BANKING_ACCOUNT_CALL_LOG);
    }
}
