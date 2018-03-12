<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Enach\Base\Entity as Enach;

class CreateEnachTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ENACH, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Enach::ID);

            $table->char(Enach::PAYMENT_ID, Payment::ID_LENGTH);

            $table->string(Enach::ACTION);

            $table->string(Enach::BANK);

            $table->longText(Enach::SIGNED_XML);

            $table->string(Enach::UMRN)
                  ->nullable();

            $table->integer(Enach::CREATED_AT);
            $table->integer(Enach::UPDATED_AT);

            $table->foreign(Enach::PAYMENT_ID)
                  ->references(Payment::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->index(Enach::UMRN);
            $table->index(Enach::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ENACH, function($table)
        {
            $table->dropForeign(Table::ENACH . '_' . Enach::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::ENACH);
    }
}
