<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\GatewayStatus\Absence\Entity as DowntimeTrace;

class CreateGatewayAbsence extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GATEWAYSTATUS_ABSENCE, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments(DowntimeTrace::ID);

            $table->string(DowntimeTrace::GATEWAY);

            $table->integer(DowntimeTrace::DOWNTIME_FROM);

            $table->dateTime(DowntimeTrace::DOWNTIME_TO);

            $table->integer(DowntimeTrace::CREATED_AT);

            $table->integer(DowntimeTrace::UPDATED_AT);

            $table->text(DowntimeTrace::REASON)
                  ->nullable();

            $table->index(DowntimeTrace::GATEWAY);
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::GATEWAYSTATUS_ABSENCE);
    }
}
