<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Slab\Entity;
use RZP\Constants\Table;

class CreateMerchantSlabs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_SLABS, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->json(Entity::SLAB)
                ->nullable();

            $table->string(Entity::TYPE, 64)
                ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)->nullable();

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::DELETED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_SLABS);
    }
}
