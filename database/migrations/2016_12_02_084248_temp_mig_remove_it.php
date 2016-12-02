<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TempMigRemoveIt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('invoices', function ($table) {

            $table->string('order_id', 14)
                  ->nullable()
                  ->change();

            $table->bigInteger('amount')
                  ->nullable()
                  ->change();

            $table->integer('deleted_at')
                  ->nullable();

            $table->index('deleted_at');
        });

        Schema::table('items', function ($table) {

            $table->integer('deleted_at')
                  ->nullable();

            $table->index('deleted_at');
        });

        Schema::table('line_items', function ($table) {

            $table->integer('deleted_at')
                  ->nullable();

            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
