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

            $table->string(BankAccount::BENEFICIARY_NAME, 255);

            $table->string(BankAccount::ACCOUNT_NUMBER, 40);

            $table->string(BankAccount::BENEFICIARY_ADDRESS1, 255);
            $table->string(BankAccount::BENEFICIARY_ADDRESS2, 255);
            $table->string(BankAccount::BENEFICIARY_ADDRESS3, 255);
            $table->string(BankAccount::BENEFICIARY_ADDRESS4, 255);

            $table->string(BankAccount::BENEFICIARY_EMAIL, 255);

            $table->string(BankAccount::BENEFICIARY_MOBILE);

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
