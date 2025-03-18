<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\FundAccount\Entity as FundAccountEntity;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::FUND_ACCOUNT, function (Blueprint $table) {
            $table->string(FundAccountEntity::CUSTOMER_NAME, 255)->nullable();
            $table->string(FundAccountEntity::LINKED_NUMBER, 50)->nullable();
            $table->string(FundAccountEntity::BANK_IFSC, 11)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::FUND_ACCOUNT, function (Blueprint $table) {
            $table->dropColumn([FundAccountEntity::LINKED_NUMBER, FundAccountEntity::BANK_IFSC, FundAccountEntity::CUSTOMER_NAME]);
        });
    }
};
