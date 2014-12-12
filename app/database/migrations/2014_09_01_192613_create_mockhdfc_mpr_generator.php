<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMockhdfcMprGenerator extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This table is not being used and is waiting to be removed!
        Schema::create('mockhdfc_mpr_generator', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string('merchant_trackid')->primary();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('mockhdfc_mpr_generator');
    }
}
