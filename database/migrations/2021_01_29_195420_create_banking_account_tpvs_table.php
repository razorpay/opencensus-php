<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;
use RZP\Models\BankingAccountTpv\Entity as BankingAccountTpv;
use RZP\Models\FundAccount\Validation\Entity as Fav;

class CreateBankingAccountTpvsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANKING_ACCOUNT_TPV, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(BankingAccountTpv::ID, BankingAccountTpv::ID_LENGTH)
                  ->primary();

            $table->char(BankingAccountTpv::BALANCE_ID, Balance\Entity::ID_LENGTH);

            $table->char(BankingAccountTpv::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->char(BankingAccountTpv::FUND_ACCOUNT_VALIDATION_ID, Fav::ID_LENGTH)
                  ->nullable();

            $table->string(BankingAccountTpv::PAYER_ACCOUNT_NUMBER);

            $table->string(BankingAccountTpv::TRIMMED_PAYER_ACCOUNT_NUMBER)
                  ->nullable();

            $table->tinyInteger(BankingAccountTpv::IS_ACTIVE)
                  ->default(0);

            $table->string(BankingAccountTpv::PAYER_NAME);

            $table->string(BankingAccountTpv::CREATED_BY);

            $table->string(BankingAccountTpv::PAYER_IFSC, 20);

            $table->string(BankingAccountTpv::STATUS, 40);

            $table->string(BankingAccountTpv::TYPE, 255);

            $table->string(BankingAccountTpv::REMARKS, 255)
                  ->nullable();

            $table->string(BankingAccountTpv::NOTES, 255)
                  ->nullable();

            $table->integer(BankingAccountTpv::CREATED_AT);

            $table->integer(BankingAccountTpv::UPDATED_AT);

            $table->index(BankingAccountTpv::MERCHANT_ID);

            $table->index(BankingAccountTpv::BALANCE_ID);

            $table->index(BankingAccountTpv::PAYER_ACCOUNT_NUMBER);

            $table->index(BankingAccountTpv::STATUS);

            $table->index(BankingAccountTpv::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::BANKING_ACCOUNT_TPV);
    }
}
