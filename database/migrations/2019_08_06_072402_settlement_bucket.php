<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Settlement\Bucket\Entity;

class SettlementBucket extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT_BUCKET, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->bigIncrements(Entity::ID)
                  ->unsigned();

            $table->char(Entity::MERCHANT_ID, UniqueIdEntity::ID_LENGTH);

            $table->string(Entity::BUCKET_TIMESTAMP);

            $table->boolean(Entity::COMPLETED)
                  ->default(0);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::BUCKET_TIMESTAMP, Entity::COMPLETED);

            $table->unique([Entity::MERCHANT_ID, Entity::BUCKET_TIMESTAMP]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::SETTLEMENT_BUCKET);
    }
}
