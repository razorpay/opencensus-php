<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Models\Payout\Entity as Payout;

class CreatePsPayoutMetaTemporary extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_payout_meta_temporary', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Payout::ID, Payout::ID_LENGTH)
                  ->primary();

            $table->integer(Payout::CREATED_AT);

            $table->integer(Payout::UPDATED_AT);

            $table->char('payout_id', Payout::ID_LENGTH);

            $table->char('meta_name', 32);

            $table->json('meta_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_payout_meta_temporary');
    }
}
