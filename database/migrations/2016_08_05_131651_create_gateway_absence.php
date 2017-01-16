<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\GatewayStatus\Absence\Entity as AbsenceEntity;
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
        Schema::create(Table::GATEWAY_ABSENCE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(AbsenceEntity::ID, AbsenceEntity::ID_LENGTH);

            $table->string(AbsenceEntity::GATEWAY, 255);

            $table->string(AbsenceEntity::ISSUER, 50)
                  ->nullable();

            $table->string(AbsenceEntity::REASON_CODE, 30);

            $table->string(AbsenceEntity::SOURCE, 30);

            $table->char(AbsenceEntity::TERMINAL_ID, TerminalEntity::ID_LENGTH)
                  ->nullable();

            $table->string(AbsenceEntity::CARD_TYPE, 10)
                  ->nullable();

            $table->string(AbsenceEntity::NETWORK, 10)
                  ->nullable();

            $table->string(AbsenceEntity::METHOD, 30);

            $table->text(AbsenceEntity::COMMENT)
                  ->nullable();

            $table->integer(AbsenceEntity::DOWNTIME_FROM);

            // TO is optional
            $table->integer(AbsenceEntity::DOWNTIME_TO)
                  ->nullable();

            $table->tinyInteger(AbsenceEntity::SCHEDULED)
                  ->default(1);

            $table->tinyInteger(AbsenceEntity::PARTIAL)
                  ->default(0);

            $table->integer(AbsenceEntity::CREATED_AT);

            $table->integer(AbsenceEntity::UPDATED_AT);

            $table->foreign(AbsenceEntity::TERMINAL_ID)
                  ->references(TerminalEntity::ID)
                  ->on(Table::TERMINAL)
                  ->on_delete('restrict');

            $table->index(AbsenceEntity::ISSUER);

            $table->index(AbsenceEntity::GATEWAY);

            $table->index(AbsenceEntity::DOWNTIME_FROM);

            $table->index(AbsenceEntity::METHOD);

            $table->index(AbsenceEntity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::GATEWAY_ABSENCE, function($table)
        {
            $table->dropForeign(
                Table::GATEWAY_ABSENCE . '_' . AbsenceEntity::TERMINAL_ID . '_foreign');
        });

        Schema::drop(Table::GATEWAY_ABSENCE);
    }
}
