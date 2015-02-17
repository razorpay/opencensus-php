<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;

class CreateDailySettlements extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::DAILY_SETTLEMENT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('id', 14)
                  ->primary();

            $table->integer('day');

            $table->string('channel', 8);

            $table->integer('amount');

            $table->text('urls');

            $table->integer('initiated_at');

            $table->integer('reconciled_at')
                  ->nullable();

            $table->integer('returned_at')
                  ->nullable();

            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index('created_at');
            $table->index('day');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::DAILY_SETTLEMENT);
    }
}
