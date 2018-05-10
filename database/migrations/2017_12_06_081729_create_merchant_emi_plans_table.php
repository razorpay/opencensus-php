<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Emi\Entity as EmiPlan;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\EmiPlans\Entity as MerchantEmiPlans;

class CreateMerchantEmiPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_EMI_PLANS, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(MerchantEmiPlans::ID, MerchantEmiPlans::ID_LENGTH)
                  ->primary();

            $table->char(MerchantEmiPlans::MERCHANT_ID, MerchantEmiPlans::ID_LENGTH);

            $table->char(MerchantEmiPlans::EMI_PLAN_ID, MerchantEmiPlans::ID_LENGTH);

            $table->integer(MerchantEmiPlans::CREATED_AT);

            $table->integer(MerchantEmiPlans::UPDATED_AT);

            $table->integer(MerchantEmiPlans::DELETED_AT)
                  ->nullable();;

            $table->unique([MerchantEmiPlans::MERCHANT_ID, MerchantEmiPlans::EMI_PLAN_ID]);

            $table->foreign(MerchantEmiPlans::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(MerchantEmiPlans::EMI_PLAN_ID)
                  ->references(EmiPlan::ID)
                  ->on(Table::EMI_PLAN)
                  ->on_delete('restrict');

            $table->index(MerchantEmiPlans::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MERCHANT_EMI_PLANS, function($table)
        {
            $table->dropForeign(
                Table::MERCHANT_EMI_PLANS . '_' . MerchantEmiPlans::MERCHANT_ID . '_foreign');

            $table->dropForeign(
                Table::MERCHANT_EMI_PLANS . '_' . MerchantEmiPlans::EMI_PLAN_ID . '_foreign');
        });

        Schema::drop(Table::MERCHANT_EMI_PLANS);
    }
}
