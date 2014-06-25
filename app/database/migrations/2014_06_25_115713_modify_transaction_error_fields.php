<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;

class ModifyTransactionErrorFields extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::TRANSACTION, function($table)
        {
            $table->dropColumn('error');

            $table->string('error_code', 20)
                  ->nullable();

            $table->string('error_description', 100)
                  ->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::TRANSACTION, function($table)
        {
            $table->string('error', 10);

            $table->dropColumn('error_code');

            $table->dropColumn('error_description');
        });
    }

}
