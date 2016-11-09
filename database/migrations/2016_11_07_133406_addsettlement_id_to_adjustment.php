<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Adjustment\Entity as Adjustment;
use RZP\Models\Settlement;


class AddsettlementIdToAdjustment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::ADJUSTMENT, function (Blueprint $table) {

            $table->char('settlement_id', Adjustment::ID_LENGTH)
                  ->nullable();

            $table->foreign('settlement_id')
                  ->references(Settlement\Entity::ID)
                  ->on(Table::SETTLEMENT)
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
        Schema::table(Table::ADJUSTMENT, function (Blueprint $table) {
            $table->dropForeign(Table::ADJUSTMENT.'_'.'settlement_id'.'_foreign');
        });
    }
}
