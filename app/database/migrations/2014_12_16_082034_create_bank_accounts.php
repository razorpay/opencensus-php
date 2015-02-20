<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Merchant;
use Models\Merchant\BankAccount;

class CreateBankAccounts extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANK_ACCOUNT, function(Blueprint $table)
        {
            $table->char(BankAccount::MERCHANT_ID, BankAccount::ID_LENGTH)
                  ->primary();

            $table->char(BankAccount::IFSC_CODE, BankAccount::IFSC_CODE_LENGTH);

            $table->string(BankAccount::ACCOUNT_NUMBER, 40);

            $table->string(BankAccount::BENEFICIARY_CODE, 6)
                  ->unique();

            $table->string(BankAccount::BENEFICIARY_NAME, 40);

            $table->string(BankAccount::BENEFICIARY_ADDRESS1, 30);
            $table->string(BankAccount::BENEFICIARY_ADDRESS2, 30)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_ADDRESS3, 30)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_ADDRESS4, 30)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_CITY, 30);
            $table->string(BankAccount::BENEFICIARY_STATE, 2);
            $table->string(BankAccount::BENEFICIARY_COUNTRY, 2);

            $table->char(BankAccount::BENEFICIARY_PIN, 6);

            $table->string(BankAccount::BENEFICIARY_EMAIL, 255);

            $table->char(BankAccount::BENEFICIARY_MOBILE, 10);

            $table->integer(BankAccount::CREATED_AT);
            $table->integer(BankAccount::UPDATED_AT);

            $table->foreign(BankAccount::MERCHANT_ID)
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
        Schema::table(Table::BANK_ACCOUNT, function($table)
        {
            $table->dropForeign(Table::BANK_ACCOUNT.'_'.BankAccount::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::BANK_ACCOUNT);
    }

}
