<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\BankAccount\Entity as BankAccount;
use RZP\Models\Settlement\Entity as Settlement;

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

            $table->char(BankAccount::ID, BankAccount::ID_LENGTH)
                  ->primary();

            $table->char(BankAccount::MERCHANT_ID, BankAccount::ID_LENGTH);

            $table->char(BankAccount::ENTITY_ID, BankAccount::ID_LENGTH);

            $table->char(BankAccount::TYPE, 8);

            $table->char(BankAccount::IFSC_CODE, BankAccount::IFSC_CODE_LENGTH);

            $table->string(BankAccount::ACCOUNT_NUMBER, 40);

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

            $table->char(BankAccount::BENEFICIARY_MOBILE, 11);

            $table->integer(BankAccount::CREATED_AT);
            $table->integer(BankAccount::UPDATED_AT);
            $table->integer(BankAccount::DELETED_AT)
                  ->nullable();

            $table->index(BankAccount::ENTITY_ID);

            $table->index(BankAccount::TYPE);

            $table->foreign(BankAccount::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });

        Schema::table(Table::SETTLEMENT, function($table)
        {
            $table->foreign(Settlement::BANK_ACCOUNT_ID)
                  ->references(Merchant\BankAccount\Entity::ID)
                  ->on(Table::BANK_ACCOUNT)
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

        Schema::table(Table::SETTLEMENT, function($table)
        {
            $table->dropForeign(
                TABLE::SETTLEMENT.'_'.Settlement::BANK_ACCOUNT_ID.'_foreign');
        });

        Schema::drop(Table::BANK_ACCOUNT);
    }
}
