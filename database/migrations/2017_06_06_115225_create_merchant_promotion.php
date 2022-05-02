<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Promotion\Entity as Promotion;
use RZP\Models\Merchant\Promotion\Entity as MerchantPromotion;

class CreateMerchantPromotion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_PROMOTION, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(MerchantPromotion::ID, MerchantPromotion::ID_LENGTH)
                  ->primary();

            $table->char(MerchantPromotion::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(MerchantPromotion::PROMOTION_ID, Promotion::ID_LENGTH);

            $table->integer(MerchantPromotion::START_TIME);

            $table->integer(MerchantPromotion::REMAINING_ITERATIONS)
                  ->unsigned();

            $table->tinyInteger(MerchantPromotion::EXPIRED);

            $table->integer(Promotion::CREATED_AT);

            $table->integer(Promotion::UPDATED_AT);

            $table->char(MerchantPromotion::AUDIT_ID,MerchantPromotion::ID_LENGTH)->nullable();

            $table->foreign(MerchantPromotion::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->onDelete('restrict');

            $table->unique([MerchantPromotion::MERCHANT_ID, MerchantPromotion::PROMOTION_ID]);

            $table->foreign(MerchantPromotion::PROMOTION_ID)
                  ->references(Promotion::ID)
                  ->on(Table::PROMOTION)
                  ->onDelete('restrict');

            $table->index(Promotion::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MERCHANT_PROMOTION, function($table)
        {
            $table->dropForeign(
                Table::MERCHANT_PROMOTION . '_' . MerchantPromotion::MERCHANT_ID.'_foreign');

            $table->dropForeign(
                Table::MERCHANT_PROMOTION . '_' . MerchantPromotion::PROMOTION_ID.'_foreign');
        });

        Schema::drop(Table::MERCHANT_PROMOTION);
    }
}
