<?php

use RZP\Constants\Table;
use RZP\Models\GeoIP\Entity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGeoIps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GEO_IP, function (Blueprint $table) {

            $table->string(Entity::IP, 45)
                  ->primary();

            $table->string(Entity::CITY, 100)
                  ->nullable();

            $table->string(Entity::STATE, 100)
                  ->nullable();

            $table->string(Entity::POSTAL, 10)
                  ->nullable();

            $table->string(Entity::COUNTRY, 4)
                  ->nullable();

            $table->string(Entity::CONTINENT, 4)
                  ->nullable();

            $table->decimal(Entity::LATITUDE, 8, 4)
                  ->nullable();

            $table->decimal(Entity::LONGITUDE, 8, 4)
                  ->nullable();

            $table->string(Entity::ISP, 100)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CITY);

            $table->index(Entity::COUNTRY);

            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::GEO_IP);
    }
}
