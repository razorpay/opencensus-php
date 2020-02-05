<?php

use RZP\Constants\Table;
use RZP\Models\BankAccount;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\PaymentsUpi\BankAccount\Entity;

class CreatePaymentsUpiBankAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENTS_UPI_BANK_ACCOUNT, function (Blueprint $table) {

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->string(Entity::BANK_CODE, 11);

            $table->string(Entity::IFSC_CODE, 11);

            $table->string(Entity::BENEFICIARY_NAME, 120)
                  ->nullable();

            $table->string(Entity::ACCOUNT_NUMBER, 40)
                  ->nullable();

            $table->unsignedInteger(Entity::CREATED_AT);
            $table->unsignedInteger(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PAYMENTS_UPI_BANK_ACCOUNT);
    }
}
