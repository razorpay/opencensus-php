<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Batch\Entity as Batch;
use RZP\Models\Payout\Entity as Payout;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\FundAccount\Entity as FundAccount;

class CreateFundAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FUND_ACCOUNT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(FundAccount::ID, FundAccount::ID_LENGTH)
                  ->primary();

            $table->char(FundAccount::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->string(FundAccount::SOURCE_TYPE)
                  ->nullable();

            $table->char(FundAccount::SOURCE_ID, FundAccount::ID_LENGTH)
                  ->nullable();

            $table->char(FundAccount::ACCOUNT_TYPE, 255);

            $table->char(FundAccount::ACCOUNT_ID, 14);

            $table->char(Payout::BATCH_ID, Batch::ID_LENGTH)
                  ->nullable();

            $table->char(FundAccount::IDEMPOTENCY_KEY, Batch::IDEMPOTENCY_ID_LENGTH)
                  ->nullable();

            $table->tinyInteger(FundAccount::ACTIVE)
                  ->default(1);

            $table->integer(FundAccount::CREATED_AT);

            $table->integer(FundAccount::UPDATED_AT);

            $table->integer(FundAccount::DELETED_AT)
                  ->nullable();

            $table->string(FundAccount::UNIQUE_HASH)
                  ->nullable();

            $table->index(FundAccount::SOURCE_ID);

            $table->index(FundAccount::ACCOUNT_ID);

            $table->index(FundAccount::ACCOUNT_TYPE);

            $table->index([FundAccount::MERCHANT_ID, FundAccount::CREATED_AT]);

            $table->index(FundAccount::CREATED_AT);

            $table->index(FundAccount::UPDATED_AT);

            $table->index(FundAccount::DELETED_AT);

            $table->index(FundAccount::UNIQUE_HASH);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::PAYOUT, function($table)
        {
            $table->dropForeign(Table::PAYOUT . '_' . Payout::FUND_ACCOUNT_ID . '_foreign');
        });

        Schema::table(Table::FUND_ACCOUNT, function($table)
        {
            $table->dropForeign(Table::FUND_ACCOUNT . '_' . FundAccount::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::FUND_ACCOUNT);
    }
}
