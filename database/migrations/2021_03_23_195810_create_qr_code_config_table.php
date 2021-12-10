<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\QrCodeConfig\Entity;

class CreateQrCodeConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::QR_CODE_CONFIG, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH)
                  ->nullable(false);

            $table->string(Entity::KEY)
                  ->nullable(false);

            $table->text(Entity::VALUE)
                  ->nullable(false);


            $table->integer(Entity::CREATED_AT)
                  ->nullable(false);

            $table->integer(Entity::UPDATED_AT)
                  ->nullable(true);

            $table->integer(Entity::DELETED_AT)
                  ->nullable(true);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::CREATED_AT);
            
            $table->index(Entity::KEY);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::QR_CODE_CONFIG);
    }
}
