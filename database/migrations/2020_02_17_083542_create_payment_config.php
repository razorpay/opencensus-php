<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Payment\Config\Entity as Config;


class CreatePaymentConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CONFIG, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Config::ID, Config::ID_LENGTH)
                  ->primary();

            $table->char(Config::MERCHANT_ID, 14);

            $table->char(Config::NAME, 255);

            $table->char(Config::TYPE, 255);

            $table->json(Config::CONFIG);

            $table->boolean(Config::IS_DEFAULT)
                  ->default(false);

            $table->json(Config::RESTRICTIONS)
                  ->default(null)
                  ->nullable();

            $table->boolean(Config::IS_DELETED)
                  ->default(false);

            $table->integer(Config::CREATED_AT);

            $table->integer(Config::UPDATED_AT);

            $table->index([Config::MERCHANT_ID, Config::NAME]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::CONFIG);
    }
}
