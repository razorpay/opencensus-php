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

            $table->integer(Ledger::AMOUNT)
                  ->unsigned();

            $table->string(Ledger::ACTION, 10);

            $table->integer(Ledger::FEE)
                  ->unsigned();

            $table->integer(Ledger::BALANCE)
                  ->unsigned();

            $table->boolean(Ledger::PENDING)
                  ->default(0);

            // Adds created_at and updated_at columns to the table
            $table->integer(Common::CREATED_AT);
            $table->integer(Common::UPDATED_AT);

            $table->index('ref');

            $table->foreign(Common::MERCHANT_ID)
                  ->references(Constants\Field\Merchant::ID)
                  ->on(Table::MERCHANT)
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
