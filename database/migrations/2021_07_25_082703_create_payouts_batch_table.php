<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payout\Batch\Entity;
use RZP\Models\Payout\Batch\Status;

class CreatePayoutsBatchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYOUTS_BATCH, function (Blueprint $table) {
            $table->engine = 'InnoDb';

            $table->char(Entity::BATCH_ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::REFERENCE_ID, 40)->nullable();

            $table->string(Entity::STATUS, 25)->default(Status::ACCEPTED);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            //indices

            $table->index([Entity::MERCHANT_ID, Entity::REFERENCE_ID]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payouts_batch');
    }
}
