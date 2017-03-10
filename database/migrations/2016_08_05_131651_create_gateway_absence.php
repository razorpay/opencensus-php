<?php

use RZP\Constants\Table;
use Illuminate\Database\Schema\Blueprint;
use RZP\Models\Terminal\Entity as Terminal;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class CreateGatewayAbsence extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GATEWAY_DOWNTIME, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(GatewayDowntime::ID, GatewayDowntime::ID_LENGTH);

            $table->string(GatewayDowntime::GATEWAY, 255);

            $table->string(GatewayDowntime::ISSUER, 50)
                  ->default(GatewayDowntime::UNKNOWN);

            $table->string(GatewayDowntime::REASON_CODE, 30);

            $table->string(GatewayDowntime::SOURCE, 30);

            $table->char(GatewayDowntime::TERMINAL_ID, Terminal::ID_LENGTH)
                  ->nullable();

            $table->string(GatewayDowntime::CARD_TYPE, 10)
                ->default(GatewayDowntime::UNKNOWN);

            $table->string(GatewayDowntime::NETWORK, 10)
                ->default(GatewayDowntime::UNKNOWN);

            $table->string(GatewayDowntime::METHOD, 30);

            $table->text(GatewayDowntime::COMMENT)
                  ->nullable();

            $table->integer(GatewayDowntime::DOWNTIME_FROM);

            // TO is optional
            $table->integer(GatewayDowntime::DOWNTIME_TO)
                  ->nullable();

            $table->tinyInteger(GatewayDowntime::SCHEDULED)
                  ->default(0);

            $table->tinyInteger(GatewayDowntime::PARTIAL)
                  ->default(0);

            $table->tinyInteger(GatewayDowntime::PUBLIC)
                   ->default(1);

            $table->integer(GatewayDowntime::CREATED_AT);

            $table->integer(GatewayDowntime::UPDATED_AT);

            $table->foreign(GatewayDowntime::TERMINAL_ID)
                  ->references(Terminal::ID)
                  ->on(Table::TERMINAL)
                  ->onDelete('restrict');

            $table->index(GatewayDowntime::ISSUER);

            $table->index(GatewayDowntime::GATEWAY);

            $table->index(GatewayDowntime::DOWNTIME_FROM);

            $table->index(GatewayDowntime::DOWNTIME_TO);

            $table->index(GatewayDowntime::METHOD);

            $table->index(GatewayDowntime::CREATED_AT);

            $table->index(GatewayDowntime::REASON_CODE);

            $table->index(GatewayDowntime::SOURCE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::GATEWAY_DOWNTIME, function($table)
        {
            $table->dropForeign(
                Table::GATEWAY_DOWNTIME . '_' . GatewayDowntime::TERMINAL_ID . '_foreign');
        });

        Schema::drop(Table::GATEWAY_DOWNTIME);
    }
}
