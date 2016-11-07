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
        Schema::create(Table::GATEWAY_ABSENCE, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(DowntimeTrace::ID, DowntimeTrace::ID_LENGTH);

            $table->string(DowntimeTrace::GATEWAY, 255);

            $table->string(DowntimeTrace::BANK, 255)
                  ->nullable();

            $table->integer(DowntimeTrace::FROM);

            // TO is optional, but we still need a value here
            // keeping this to max time possible ~ Infinite time
            $table->integer(DowntimeTrace::TO)
                  ->nullable();

            $table->integer(DowntimeTrace::CREATED_AT);

            $table->integer(DowntimeTrace::UPDATED_AT);

            $table->text(DowntimeTrace::REASON, 500)
                  ->nullable();

            $table->tinyInteger(DowntimeTrace::SCHEDULED)
                    ->default(1);

            $table->tinyInteger(DowntimeTrace::PARTIAL)
                    ->default(0);

            $table->index(DowntimeTrace::GATEWAY);

            $table->index(DowntimeTrace::FROM);

            $table->index(DowntimeTrace::TO);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::GATEWAY_ABSENCE);
    }
}
