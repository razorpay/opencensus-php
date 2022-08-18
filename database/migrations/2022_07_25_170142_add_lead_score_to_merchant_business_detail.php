<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;

class AddLeadScoreToMerchantBusinessDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::MERCHANT_BUSINESS_DETAIL, function (Blueprint $table) {
            $table->json(BusinessDetailEntity::LEAD_SCORE_COMPONENTS)
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchant_business_detail', function (Blueprint $table) {
            //
        });
    }
}
