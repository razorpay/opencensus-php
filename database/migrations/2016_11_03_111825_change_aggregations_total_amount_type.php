<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAggregationsTotalAmountType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // composer require doctrine/dbal
        // Schema::table('aggregations', function (Blueprint $table) {
        //     $table->unsignedBigInteger('total_amount')->change();
        // });

        DB::statement('ALTER TABLE `aggregations` MODIFY `total_amount` BIGINT UNSIGNED');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('aggregations', function (Blueprint $table) {
        //     $table->unsignedInteger('total_amount')->change();
        // });

        DB::statement('ALTER TABLE `aggregations` MODIFY `total_amount` INTEGER UNSIGNED');
    }
}
