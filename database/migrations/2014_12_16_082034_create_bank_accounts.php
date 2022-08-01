<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\BankAccount\Entity as BankAccount;
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

            $table->char(BankAccount::ENTITY_ID, BankAccount::ID_LENGTH)
                  ->nullable();

            $table->char(BankAccount::TYPE, 40)
                  ->nullable();

            $table->char(BankAccount::BENEFICIARY_CODE, 10)
                  ->nullable()
                  ->unique();

            $table->char(BankAccount::IFSC_CODE, BankAccount::IFSC_CODE_LENGTH)
                  ->nullable();

            $table->string(BankAccount::ACCOUNT_NUMBER, 40);

            $table->string(BankAccount::ACCOUNT_TYPE, 255)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_NAME, 120);

            $table->string(BankAccount::REGISTERED_BENEFICIARY_NAME, 255)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_ADDRESS1, 30)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_ADDRESS2, 30)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_ADDRESS3, 30)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_ADDRESS4, 30)
                  ->nullable();

            $table->tinyInteger(BankAccount::MOBILE_BANKING_ENABLED)
                  ->nullable();

            $table->tinyInteger(BankAccount::GATEWAY_SYNC)
                  ->nullable();

            $table->string(BankAccount::MPIN, 255)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_CITY, 30)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_STATE, 2)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_COUNTRY, 2)
                  ->nullable();

            $table->char(BankAccount::BENEFICIARY_PIN, 6)
                  ->nullable();

            $table->string(BankAccount::BENEFICIARY_EMAIL, 255)
                  ->nullable();

            $table->char(BankAccount::BENEFICIARY_MOBILE, 32)
                  ->nullable();

            $table->integer(BankAccount::FTS_FUND_ACCOUNT_ID)
                  ->nullable();

            $table->tinyInteger(BankAccount::VIRTUAL)
                  ->default(0);

            $table->text(BankAccount::NOTES)
                  ->nullable();

            $table->integer(BankAccount::CREATED_AT);
            $table->integer(BankAccount::UPDATED_AT);
            $table->integer(BankAccount::DELETED_AT)
                  ->nullable();

            $table->index([BankAccount::ENTITY_ID, BankAccount::TYPE]);

            $table->index(BankAccount::TYPE);

            $table->index(BankAccount::ACCOUNT_NUMBER);

            $table->index(BankAccount::CREATED_AT);

            $table->index(BankAccount::UPDATED_AT);

            $table->index(BankAccount::FTS_FUND_ACCOUNT_ID);

            $table->foreign(BankAccount::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });

        Schema::table(Table::SETTLEMENT, function($table)
        {
            $table->foreign(Settlement::BANK_ACCOUNT_ID)
                  ->references(BankAccount::ID)
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
                Table::SETTLEMENT.'_'.Settlement::BANK_ACCOUNT_ID.'_foreign');
        });

        Schema::drop(Table::BANK_ACCOUNT);
    }
}
