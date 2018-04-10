<?php

use RZP\Constants\Table;
use RZP\Models\State;
use RZP\Models\State\Reason\Entity as StateReason;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActionStateReasons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::STATE_REASON, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->bigIncrements(StateReason::ID)->unsigned();

            $table->char(StateReason::STATE_ID, StateReason::ID_LENGTH);

            $table->string(StateReason::REASON_TYPE, 255);

            $table->string(StateReason::REASON_CATEGORY, 255);

            $table->string(StateReason::REASON_CODE, 255);

            $table->integer(StateReason::CREATED_AT);

            $table->integer(StateReason::UPDATED_AT);

             $table->foreign(StateReason::STATE_ID)
                   ->references(State\Entity::ID)
                   ->on(Table::STATE)
                   ->on_delete('restrict');

            $table->index(StateReason::REASON_TYPE);
            $table->index(StateReason::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::STATE_REASON, function (Blueprint $table)
        {
            $table->dropForeign(Table::STATE_REASON . '_' . StateReason::STATE_ID . '_foreign');
        });

        Schema::drop(Table::STATE_REASON);
    }
}
