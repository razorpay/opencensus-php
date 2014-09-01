<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Gateway\Hdfc;
use Models\Base\UniqueIdEntity;

class CreateHdfcGateway extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hdfc', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('trackid', UniqueIdEntity::ID_LENGTH);

            $table->bigInteger('gateway_transaction_id')
                  ->unsigned()
                  ->nullable();

            $table->string('action', 1);

            $table->string('enroll_result', Hdfc\Constants::ENROLL_RESULT_LENGTH)
                  ->nullable();

            $table->string('status', Hdfc\Constants::STATUS_LENGTH);

            $table->string('result', Hdfc\Constants::AUTH_RESULT_LENGTH)
                  ->nullable();

            $table->string('eci', Hdfc\Constants::ECI_LENGTH)
                  ->nullable();

            $table->string('auth', Hdfc\Constants::AUTH_LENGTH)
                  ->nullable();

            $table->string('ref', Hdfc\Constants::REF_LENGTH)
                  ->nullable();

            $table->string('avr', Hdfc\Constants::AVR_LENGTH)
                  ->nullable();

            $table->string('postdate', Hdfc\Constants::POSTDATE_LENGTH)
                  ->nullable();

            $table->string('error_code', Hdfc\Constants::ERROR_CODE_LENGTH)
                  ->nullable();

            $table->string('error_text', Hdfc\Constants::ERROR_TEXT_LENGTH)
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('trackid')
                  ->references('id')
                  ->on('transactions')
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hdfc', function($table)
        {
            $table->dropForeign('hdfc_trackid_foreign');
        });

        Schema::drop('hdfc');
    }

}
