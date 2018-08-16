<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Esigner\Base\Entity as Esigner;

class CreateEsginerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ESIGNER, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Esigner::ID);

            $table->char(Esigner::PAYMENT_ID, Payment::ID_LENGTH);

            $table->string(Esigner::GATEWAY);

            $table->string(Esigner::ACTION);

            $table->string(Esigner::MANDATE_ID)
                  ->nullable();

            $table->string(Esigner::STATUS)
                  ->nullable();

            $table->string(Esigner::ACCOUNT_TYPE)
                  ->nullable();

            $table->string(Esigner::ACCOUNT_NUMBER)
                  ->nullable();

            $table->string(Esigner::AGENT_TYPE, 4)
                  ->nullable();

            $table->string(Esigner::AGENT_ID)
                  ->nullable();

            $table->string(Esigner::AGENT_NAME)
                  ->nullable();

            $table->string(Esigner::SEQUENCE_TYPE)
                  ->nullable();

            $table->string(Esigner::FREQUENCY_TYPE)
                  ->nullable();

            $table->string(Esigner::START_DATE)
                  ->nullable();

            $table->string(Esigner::END_DATE)
                  ->nullable();

            $table->string(Esigner::AMOUNT_TYPE)
                  ->nullable();

            $table->string(Esigner::AMOUNT)
                  ->nullable();

            $table->string(Esigner::CATEGORY_CODE, 4)
                  ->nullable();

            $table->string(Esigner::ERROR_CODE)
                ->nullable();

            $table->string(Esigner::ERROR_MESSAGE)
                ->nullable();

            $table->integer(Esigner::CREATED_AT);

            $table->integer(Esigner::UPDATED_AT);

            $table->foreign(Esigner::PAYMENT_ID)
                ->references(Payment::ID)
                ->on(Table::PAYMENT)
                ->on_delete('restrict');

            $table->index(Esigner::CREATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ESIGNER, function($table)
        {
            $table->dropForeign(Table::ESIGNER . '_' . Enach::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::ESIGNER);
    }
}
