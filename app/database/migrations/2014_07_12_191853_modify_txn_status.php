<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Transaction\Entity as Transaction;

class ModifyTxnStatus extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::TRANSACTION, function($table)
        {
            \DB::raw('ALTER TABLE ' . Table::TRANSACTION . ' status status ENUM('.
                                        'open',
                                        'auth',
                                        'authorized',
                                        'captured',
                                        'refunded',
                                        'failed');


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
