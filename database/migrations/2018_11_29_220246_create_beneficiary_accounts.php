<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Beneficiary\Entity as Beneficiary;
use RZP\Models\Merchant\Entity as Merchant;

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
            $table->dropForeign(Table::BENEFICIARY_ACCOUNT . '_' . Beneficiary::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::BENEFICIARY_ACCOUNT);
    }
}
