<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Emi\Entity as EmiPlan;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Emi\MerchantSubvention\Entity as MerchantSubvention;

class CreateEmiMerchantSubventionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::EMI_MERCHANT_SUBVENTION, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(MerchantSubvention::ID, MerchantSubvention::ID_LENGTH)
                  ->primary();

            $table->char(MerchantSubvention::MERCHANT_ID, MerchantSubvention::ID_LENGTH);

            $table->char(MerchantSubvention::EMI_PLAN_ID, MerchantSubvention::ID_LENGTH);

            $table->integer(MerchantSubvention::MERCHANT_PAYBACK)
                  ->default(0);

            $table->integer(MerchantSubvention::CREATED_AT);

            $table->integer(MerchantSubvention::UPDATED_AT);

            $table->unique([MerchantSubvention::MERCHANT_ID, MerchantSubvention::EMI_PLAN_ID]);

            $table->foreign(MerchantSubvention::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(MerchantSubvention::EMI_PLAN_ID)
                  ->references(EmiPlan::ID)
                  ->on(Table::EMI_PLAN)
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
        Schema::table(Table::EMI_MERCHANT_SUBVENTION, function($table)
        {
            $table->dropForeign(
                Table::EMI_MERCHANT_SUBVENTION . '_' . MerchantSubvention::MERCHANT_ID . '_foreign');

            $table->dropForeign(
                Table::EMI_MERCHANT_SUBVENTION . '_' . MerchantSubvention::EMI_PLAN_ID . '_foreign');
        });

        Schema::drop(Table::EMI_MERCHANT_SUBVENTION);
    }
}
