<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Models\Payment\Entity as Payment;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::PAYMENT, function(Blueprint $table) {
            $table->index([Payment::MERCHANT_ID, Payment::STATUS, Payment::CREATED_AT],
                          "payments_merchant_id_status_created_at_index_all_replicas");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::PAYMENT, function(Blueprint $table) {
            $table->dropIndex(
                "payments_merchant_id_status_created_at_index_all_replicas");
        });
    }
}
