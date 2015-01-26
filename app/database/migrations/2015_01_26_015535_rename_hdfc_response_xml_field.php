<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameHdfcResponseXmlField extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hdfc_response_xml', function(Blueprint $table)
        {
            $table->renameColumn('trackid', 'payment_id');

            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hdfc_response_xml', function(Blueprint $table)
        {
            $table->dropIndex('payment_id');

            $table->renameColumn('payment_id', 'trackid');
        });
    }
}
