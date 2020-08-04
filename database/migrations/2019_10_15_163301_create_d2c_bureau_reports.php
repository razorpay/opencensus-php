<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use \RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\D2cBureauReport\Entity;

class CreateD2cBureauReports extends Migration
{
    public function up()
    {
        Schema::create(Table::D2C_BUREAU_REPORT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, RZP\Models\Merchant\Entity::ID_LENGTH);

            $table->char(Entity::USER_ID, RZP\Models\User\Entity::ID_LENGTH);

            $table->char(Entity::D2C_BUREAU_DETAIL_ID, RZP\Models\D2cBureauDetail\Entity::ID_LENGTH);

            $table->string(Entity::PROVIDER);

            $table->string(Entity::ERROR_CODE)
                  ->nullable();

            $table->unsignedSmallInteger(Entity::SCORE)
                  ->nullable();

            $table->unsignedSmallInteger(Entity::NTC_SCORE)
                  ->nullable();

            $table->json(Entity::REPORT)
                  ->nullable();

            $table->char(Entity::UFH_FILE_ID)
                  ->nullable();

            $table->char(Entity::CSV_REPORT_UFH_FILE_ID)
                  ->nullable();

            $table->tinyInteger(Entity::INTERESTED)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index([Entity::MERCHANT_ID, Entity::D2C_BUREAU_DETAIL_ID]);

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
        Schema::dropIfExists(Table::D2C_BUREAU_REPORT);
    }
}
