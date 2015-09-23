<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Models\Base\UniqueIdEntity;

class CreateNetbanking extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('netbanking', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->string('action');
            $table->string('amount');
            $table->string('bank');
            $table->boolean('received')->default(0);
            $table->string('client_code')->nullable();
            $table->string('merchant_code')->nullable();
            $table->string('bank_payment_id')->nullable();
            $table->string('status')->nullable();
            $table->string('error_message')->nullable();
            $table->string('reference1')->nullable();
            $table->string('reference2')->nullable();
            $table->string('date')->nullable();

            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');

            $table->index('received');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('netbanking', function($table)
        {
            $table->dropForeign('netbanking_payment_id_foreign');
        });

        Schema::drop('netbanking');
    }

}
