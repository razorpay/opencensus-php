<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

class CreateNodalStatements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::NODAL_STATEMENT, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('id', 14)
                  ->primary();

            $table->string('bank_name', 255);

            $table->string('sender_account_number', 100);

            $table->string('receiver_account_number', 100);

            $table->text('particulars')
                  ->nullable();

            // UTR and TxnType may not be present for outward transactions
            $table->string('bank_reference_number', 50)
                  ->nullable();

            $table->bigInteger('debit')
                  ->unsigned();

            $table->bigInteger('credit')
                  ->unsigned();

            $table->bigInteger('balance')
                  ->unsigned();

            $table->integer('transaction_date')
                  ->nullable();

            $table->integer('processed_on')
                  ->nullable();

            $table->string('mode', 50)
                  ->nullable();

            $table->string('cms', 30)
                  ->nullable();

            $table->unique(
                ['debit', 'credit', 'balance', 'transaction_date']);

            $table->string('reference1')
                  ->nullable();

            $table->string('reference2')
                  ->nullable();

            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index('receiver_account_number');

            $table->index('sender_account_number');

            $table->index('bank_reference_number');

            $table->index('created_at');

            $table->index('updated_at');
        });
        //
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::NODAL_STATEMENT);
    }
}
