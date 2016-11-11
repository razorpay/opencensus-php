<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\GatewayStatus\Absence\Entity as DowntimeTrace;
use RZP\Models\Terminal\Entity as TerminalEntity;

class CreateGatewayAbsence extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GATEWAY_ABSENCE, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(DowntimeTrace::ID, DowntimeTrace::ID_LENGTH);

            $table->string(DowntimeTrace::GATEWAY, 255);

            $table->string(DowntimeTrace::ISSUER, 50)
                ->nullable();

            $table->string(DowntimeTrace::REASON_CODE, 30);

            $table->char(DowntimeTrace::TERMINAL_ID, TerminalEntity::ID_LENGTH)
                ->nullable();

            $table->string(DowntimeTrace::CARD_TYPE, 10)
                ->nullable();

            $table->string(DowntimeTrace::NETWORK, 10)
                ->nullable();

            $table->string(DowntimeTrace::METHOD, 30);

            $table->text(DowntimeTrace::COMMENT)
                ->nullable();

            $table->integer(DowntimeTrace::FROM);

            // TO is optional, but we still need a value here
            // keeping this to max time possible ~ Infinite time
            $table->integer(DowntimeTrace::TO)
                  ->nullable();

            $table->tinyInteger(DowntimeTrace::SCHEDULED)
                ->default(1);

            $table->tinyInteger(DowntimeTrace::PARTIAL)
                ->default(0);

            $table->integer(DowntimeTrace::CREATED_AT);

            $table->integer(DowntimeTrace::UPDATED_AT);

            $table->foreign(DowntimeTrace::TERMINAL_ID)
                ->references(TerminalEntity::ID)
                ->on(Table::TERMINAL)
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
        Schema::table(Table::GATEWAY_ABSENCE, function($table) {
            $table->dropForeign(
                TABLE::GATEWAY_ABSENCE . '_' . DowntimeTrace::TERMINAL_ID . '_foreign');
        });

        Schema::drop(Table::GATEWAY_ABSENCE);
    }
}
