<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\PartnerBankHealth\Entity;

class CreatePartnerBankHealthTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PARTNER_BANK_HEALTH, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->string(Entity::EVENT_TYPE, 127)
                ->nullable(false);

            $table->json(Entity::VALUE)
                ->nullable(false);

            $table->integer(Entity::CREATED_AT)
                ->nullable(false);

            $table->integer(Entity::UPDATED_AT)
                ->nullable(false);

            //Indexes
            $table->index(Entity::EVENT_TYPE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PARTNER_BANK_HEALTH);
    }
}
