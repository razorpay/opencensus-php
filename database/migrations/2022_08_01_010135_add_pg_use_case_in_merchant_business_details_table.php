<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class AddPgUseCaseInMerchantBusinessDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::MERCHANT_BUSINESS_DETAIL, function (Blueprint $table) {
            $table->string('pg_use_case', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MERCHANT_BUSINESS_DETAIL, function (Blueprint $table) {
            $table->dropColumn('pg_use_case');
        });
    }
}
