<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Field\Ledger;
use Constants\Field\Common;
use Constants\Table;

class CreateLedger  extends Migration {

    /**
     * Runs the migration
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::LEDGER, function(Blueprint $table){
            $table->engine = 'InnoDB';

            $table->char(Ledger::ID, Constants\Fields::ID_LENGTH)
                  ->primary();

            $table->char('ref', Constants\Fields::ID_LENGTH);

            $table->integer(Common::MERCHANT_ID)
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
            $table->integer(Common::CREATED_AT);
            $table->integer(Common::UPDATED_AT);

            $table->index('ref');

            $table->foreign(Common::MERCHANT_ID)
                  ->references(Constants\Field\Merchant::ID)
                  ->on(Table::MERCHANTS)
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
        Schema::table(Table::LEDGER, function($table){
            $table->dropForeign('ledger_merchant_id_foreign');
        });

        Schema::drop(Table::LEDGER);
    }

}
