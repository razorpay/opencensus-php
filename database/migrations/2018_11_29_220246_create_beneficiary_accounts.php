<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Beneficiary\Entity as Beneficiary;
use RZP\Models\Beneficiary\Account\Entity as BeneficiaryAccount;

class CreateBeneficiaryAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BENEFICIARY_ACCOUNT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(BeneficiaryAccount::ID, 14)
                  ->primary();

            $table->char(BeneficiaryAccount::MERCHANT_ID, 14);

            $table->char(BeneficiaryAccount::BENEFICIARY_ID, 14);

            $table->char(BeneficiaryAccount::ACCOUNT_TYPE, 255);

            $table->char(BeneficiaryAccount::ACCOUNT_ID, 14);

            $table->tinyInteger(BeneficiaryAccount::ACTIVE)
                  ->default(1);

            $table->integer(BeneficiaryAccount::CREATED_AT);

            $table->integer(BeneficiaryAccount::UPDATED_AT);

            $table->integer(BeneficiaryAccount::DELETED_AT)
                  ->nullable();

            $table->index([BeneficiaryAccount::ACCOUNT_ID, BeneficiaryAccount::ACCOUNT_TYPE]);

            $table->index([BeneficiaryAccount::MERCHANT_ID, BeneficiaryAccount::CREATED_AT]);

            $table->index(BeneficiaryAccount::DELETED_AT);

            $table->foreign(BeneficiaryAccount::BENEFICIARY_ID)
                  ->references(Beneficiary::ID)
                  ->on(Table::BENEFICIARY)
                  ->on_delete('restrict');

            $table->foreign(BeneficiaryAccount::MERCHANT_ID)
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
        Schema::table(Table::BENEFICIARY_ACCOUNT, function($table)
        {
            $table->dropForeign(Table::BENEFICIARY_ACCOUNT . '_' . BeneficiaryAccount::BENEFICIARY_ID . '_foreign');

            $table->dropForeign(Table::BENEFICIARY_ACCOUNT . '_' . BeneficiaryAccount::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::BENEFICIARY_ACCOUNT);
    }
}
