<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Payout\Entity as Payout;
use RZP\Models\FundAccount\Entity as FundAccount;

class CreatePsBulkIdempotencyKeys extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_bulk_idempotency_keys', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Payout::ID, 14)->primary();

            $table->char(Payout::MERCHANT_ID, Payout::ID_LENGTH);

            $table->char(Payout::BATCH_ID, Payout::ID_LENGTH)
                  ->nullable();

            $table->char(Payout::IDEMPOTENCY_KEY, 255);

            $table->string(FundAccount::SOURCE_TYPE, 255)
                  ->nullable();

            $table->char(FundAccount::SOURCE_ID, 14)
                  ->nullable();

            $table->integer(Payout::CREATED_AT);

            $table->integer(Payout::UPDATED_AT);

            $table->unique([Payout::IDEMPOTENCY_KEY, Payout::MERCHANT_ID]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_bulk_idempotency_keys');
    }
}
