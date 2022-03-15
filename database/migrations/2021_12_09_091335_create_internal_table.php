<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Internal\Entity;

class CreateInternalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::INTERNAL, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->char(Entity::TRANSACTION_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->char(Entity::STATUS, 14);

            $table->string(Entity::TYPE, 6);

            $table->char(Entity::CURRENCY, 3);

            $table->bigInteger(Entity::AMOUNT)
                ->unsigned();

            $table->bigInteger(Entity::BASE_AMOUNT)
                ->unsigned();

            $table->string(Entity::UTR, 45);

            $table->string(Entity::BANK_NAME, 255)
                ->nullable();

            $table->string(Entity::MODE, 30)
                ->nullable();

            $table->string(Entity::ENTITY_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->string(Entity::ENTITY_TYPE, 45)
                ->nullable();

            $table->string(Entity::REMARKS)
                ->nullable();

            $table->integer(Entity::TRANSACTION_DATE)
                ->nullable();

            $table->integer(Entity::RECONCILED_AT)
                ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                ->unsigned()
                ->nullable();

            // Indexes

            $table->index([Entity::ENTITY_ID]);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('internal', function (Blueprint $table) {
            //
        });

        Schema::dropIfExists(Table::INTERNAL);
    }
}
