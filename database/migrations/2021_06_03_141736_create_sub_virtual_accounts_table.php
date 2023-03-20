<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\SubVirtualAccount\Entity as SubVirtualAccount;

class CreateSubVirtualAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SUB_VIRTUAL_ACCOUNT, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(SubVirtualAccount::ID, SubVirtualAccount::ID_LENGTH)
                  ->primary();

            $table->string(SubVirtualAccount::MASTER_MERCHANT_ID, SubVirtualAccount::ID_LENGTH);

            $table->string(SubVirtualAccount::SUB_MERCHANT_ID, SubVirtualAccount::ID_LENGTH);

            $table->string(SubVirtualAccount::MASTER_BALANCE_ID, SubVirtualAccount::ID_LENGTH);

            $table->string(SubVirtualAccount::MASTER_ACCOUNT_NUMBER, 40);

            $table->string(SubVirtualAccount::NAME, 255);

            $table->string(SubVirtualAccount::SUB_ACCOUNT_NUMBER, 40);

            $table->string(SubVirtualAccount::SUB_ACCOUNT_TYPE, 30)
                  ->default(\RZP\Models\SubVirtualAccount\Type::DEFAULT);

            $table->tinyInteger(SubVirtualAccount::ACTIVE)
                  ->default(1);

            $table->integer(SubVirtualAccount::CREATED_AT);

            $table->integer(SubVirtualAccount::UPDATED_AT)
                  ->nullable();

            $table->index(SubVirtualAccount::MASTER_MERCHANT_ID);

            $table->index(SubVirtualAccount::MASTER_ACCOUNT_NUMBER);

            $table->index(SubVirtualAccount::SUB_ACCOUNT_NUMBER);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SUB_VIRTUAL_ACCOUNT);
    }
}
