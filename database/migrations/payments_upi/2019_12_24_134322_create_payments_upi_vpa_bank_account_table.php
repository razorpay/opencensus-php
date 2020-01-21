<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\PaymentsUpi\Vpa\BankAccount\Entity;

class CreatePaymentsUpiVpaBankAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENTS_UPI_VPA_BANK_ACCOUNT, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::VPA_ID, Entity::ID_LENGTH);

            $table->char(Entity::BANK_ACCOUNT_ID, Entity::ID_LENGTH);

            $table->unsignedInteger(Entity::LAST_USED_AT)
                  ->nullable();

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
        Schema::dropIfExists(Table::PAYMENTS_UPI_VPA_BANK_ACCOUNT);
    }
}
