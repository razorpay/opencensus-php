<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Contact\Entity as Contact;
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

            $table->char(FundAccount::CONTACT_ID, Contact::ID_LENGTH)
                  ->nullable();

            $table->char(FundAccount::ACCOUNT_TYPE, 255);

            $table->char(FundAccount::ACCOUNT_ID, 14);

            $table->tinyInteger(FundAccount::ACTIVE)
                  ->default(1);

            $table->integer(FundAccount::CREATED_AT);

            $table->integer(FundAccount::UPDATED_AT);

            $table->integer(FundAccount::DELETED_AT)
                  ->nullable();

            $table->index(FundAccount::CONTACT_ID);

            $table->index(FundAccount::ACCOUNT_ID);

            $table->index(FundAccount::ACCOUNT_TYPE);

            $table->index([FundAccount::MERCHANT_ID, FundAccount::CREATED_AT]);

            $table->index(FundAccount::CREATED_AT);

            $table->index(FundAccount::UPDATED_AT);

            $table->index(FundAccount::DELETED_AT);

            $table->foreign(FundAccount::CONTACT_ID)
                  ->references(Contact::ID)
                  ->on(Table::CONTACT)
                  ->on_delete('restrict');

            $table->foreign(FundAccount::MERCHANT_ID)
                  ->references(Merchant::ID)
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
        Schema::table(Table::FUND_ACCOUNT, function($table)
        {
            $table->dropForeign(Table::FUND_ACCOUNT . '_' . FundAccount::CONTACT_ID . '_foreign');

            $table->dropForeign(Table::FUND_ACCOUNT . '_' . FundAccount::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::FUND_ACCOUNT);
    }
}
