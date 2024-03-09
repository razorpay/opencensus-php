<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Emi\Entity as EmiPlan;
use RZP\Constants\Table;
class AddingColumnToEmiPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::EMI_PLAN, function (Blueprint $table) {
            $table->string(EmiPlan::SOURCE_CHANNEL)
                ->default('online')
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
        Schema::table(Table::EMI_PLAN, function (Blueprint $table) {
            $table->dropColumn(EmiPlan::SOURCE_CHANNEL);
        });
    }
}
