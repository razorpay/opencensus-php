<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Payout\Entity as Payout;

class CreatePsPayoutLogs extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_payout_logs', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Payout::ID, Payout::ID_LENGTH)
                  ->primary();

            $table->integer(Payout::CREATED_AT);

            $table->integer(Payout::UPDATED_AT);

            $table->char('payout_id', Payout::ID_LENGTH);

            $table->char('from', 50);

            $table->char('to', 50);

            $table->char('event', 50);

            $table->char('mode', 50);

            $table->char('triggered_by', 50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_payout_logs');
    }
}
