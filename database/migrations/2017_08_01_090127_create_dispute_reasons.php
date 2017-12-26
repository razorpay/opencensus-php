<?php

use RZP\Constants\Table;
use RZP\Models\Dispute\Reason\Entity as DisputeReason;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDisputeReasons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::DISPUTE_REASON, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(DisputeReason::ID, DisputeReason::ID_LENGTH)
                  ->primary();

            $table->string(DisputeReason::NETWORK, 50);

            $table->string(DisputeReason::GATEWAY_CODE, 50);

            $table->string(DisputeReason::GATEWAY_DESCRIPTION, 255);

            $table->string(DisputeReason::CODE, 255);

            $table->string(DisputeReason::DESCRIPTION, 255);

            $table->integer(DisputeReason::CREATED_AT);

            $table->integer(DisputeReason::UPDATED_AT);

            $table->index(DisputeReason::GATEWAY_CODE);

            $table->index(DisputeReason::CODE);

            $table->index(DisputeReason::NETWORK);

            $table->index(DisputeReason::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::DISPUTE_REASON);
    }
}
