<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLedger  extends Migration {

    /**
     * Runs the migration
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ledger', function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->char('id', Constants\Fields::ID_LENGTH)
                  ->primary();

            $table->char('ref', Constants\Fields::ID_LENGTH);

            $table->integer('merchant_id')
                  ->unsigned();

            $table->integer('amount')
                  ->unsigned();

            $table->string('action', 10);

            $table->integer('fee')
                  ->unsigned();

            $table->integer('balance')
                  ->unsigned();

            $table->boolean('pending')
                  ->default(0);

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index('ref');

            $table->foreign('merchant_id')
                  ->references('id')
                  ->on('merchants')
                  ->on_delete('restrict');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ledger', function($table){
            $table->dropForeign('ledger_merchant_id_foreign');
        });

        Schema::drop('ledger');
    }

}
