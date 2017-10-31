<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTransferModeColumnToFundTransferAttemptTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fund_transfer_attempts', function (Blueprint $table) {
            $table->string('mode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fund_transfer_attempts', function (Blueprint $table) {
            $table->dropColumn(['mode']);
        });
    }
}
