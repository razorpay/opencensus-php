<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Gateway\HdfcGateway\HdfcGatewayConstants;
use Models\Base\UniqueIdEntity;

class CreateHdfcGateway extends Migration {

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

            $table->bigInteger('transactionid')
                  ->unsigned()
                  ->nullable();

            $table->string('action', 1);

            $table->string('enroll_result', HdfcGatewayConstants::ENROLL_RESULT_LENGTH);

            $table->string('status', HdfcGatewayConstants::STATUS_LENGTH);

            $table->string('auth_result', HdfcGatewayConstants::AUTH_RESULT_LENGTH)
                  ->nullable();

            $table->string('eci', HdfcGatewayConstants::ECI_LENGTH);

            $table->string('auth', HdfcGatewayConstants::AUTH_LENGTH)
                  ->nullable();

            $table->string('ref', HdfcGatewayConstants::REF_LENGTH)
                  ->nullable();

            $table->string('avr', HdfcGatewayConstants::AVR_LENGTH)
                  ->nullable();

            $table->string('postdate', HdfcGatewayConstants::POSTDATE_LENGTH)
                  ->nullable();

            $table->string('error_code', HdfcGatewayConstants::ERROR_CODE_LENGTH)
                  ->nullable();

            $table->string('error_text', HdfcGatewayConstants::ERROR_TEXT_LENGTH)
                  ->nullable();

            // @todo: remove this field
            $table->string('error_service')
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
