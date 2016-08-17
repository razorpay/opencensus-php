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

            $table->string(DowntimeTrace::BANK)
                    ->nullable();

            $table->integer(DowntimeTrace::FROM);

            // TO is optional, but we still need a value here
            // keeping this to max time possible ~ Infinite time
            $table->integer(DowntimeTrace::TO)
                    ->default(DowntimeTrace::END_OF_TIME);

            $table->integer(DowntimeTrace::CREATED_AT);

            $table->integer(DowntimeTrace::UPDATED_AT);

            $table->text(DowntimeTrace::REASON)
                  ->nullable();

            $table->index(DowntimeTrace::GATEWAY);

            $table->unique(array(DowntimeTrace::GATEWAY, DowntimeTrace::FROM, DowntimeTrace::TO));
            
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
