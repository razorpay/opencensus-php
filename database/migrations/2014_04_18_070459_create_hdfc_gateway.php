<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Gateway\Hdfc;
use RZP\Models\Base\UniqueIdEntity;

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

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);

            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)
                  ->nullable();

            $table->bigInteger('gateway_transaction_id')
                  ->unsigned()
                  ->nullable();

            $table->bigInteger('gateway_payment_id')
                  ->unsigned()
                  ->nullable();

            $table->string('action', 1);

            $table->tinyInteger('received')->nullable();

            $table->string('amount', 10);

            $table->string('currency', 3)
                  ->nullable();

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

            $table->string('error_code2')
                  ->nullable();

            $table->string('error_text')
                  ->nullable();

            $table->string('arn_no')
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index('refund_id');
            $table->index('gateway_transaction_id');
            $table->index('gateway_payment_id');
            $table->index('received');
            $table->index('ref');
            $table->index('created_at');
            $table->index('auth');
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
            $table->dropForeign('hdfc_payment_id_foreign');
        });

        Schema::drop('hdfc');
    }
}
