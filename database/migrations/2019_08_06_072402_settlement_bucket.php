<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Settlement\Bucket\Entity;

class SettlementBucket extends Migration
{
    const UNIQUE_INDEX_NAME = 'settlement_bucket_mid_balance_type_bucket_timestamp';

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

            $table->char(Entity::BALANCE_TYPE)
                  ->default(Balance\Type::PRIMARY);

            $table->string(Entity::BUCKET_TIMESTAMP);

            $table->boolean(Entity::COMPLETED)
                  ->default(0);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::BUCKET_TIMESTAMP);

            $table->unique([Entity::MERCHANT_ID, Entity::BALANCE_TYPE, Entity::BUCKET_TIMESTAMP], self::UNIQUE_INDEX_NAME);
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
