<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

class CreateMerchantMap extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_MAP, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('merchant_id');

            $table->char('entity_id');

            $table->char('entity_type'); // admin or group

            $table->unique(['merchant_id', 'entity_id']);

            $table->foreign('merchant_id')
                  ->references('id')
                  ->on(Table::MERCHANT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MERCHANT_MAP);
    }
}
