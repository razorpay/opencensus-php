<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Gateway\MethodDowntime\Entity as MethodDowntime;

class CreateMethodDowntimes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::METHOD_DOWNTIME, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(MethodDowntime::ID, MethodDowntime::ID_LENGTH)
                  ->primary();

            $table->string(MethodDowntime::METHOD, 30);

            $table->integer(MethodDowntime::BEGIN);

            $table->integer(MethodDowntime::END)
                  ->nullable();

            $table->integer(MethodDowntime::CREATED_AT);

            $table->integer(MethodDowntime::UPDATED_AT);

            $table->index(MethodDowntime::BEGIN);
            $table->index(MethodDowntime::END);
            $table->index(MethodDowntime::METHOD);
            $table->index(MethodDowntime::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::METHOD_DOWNTIME);
    }
}
