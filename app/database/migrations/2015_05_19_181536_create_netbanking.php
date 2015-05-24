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
            $table->string('client_code')->nullable();
            $table->string('merchant_code')->nullable();
            $table->string('bank_payment_id')->nullable();
            $table->string('error_message')->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
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
        Schema::table('netbanking', function($table)
        {
            $table->dropForeign('netbanking_payment_id_foreign');
        });

        Schema::drop('netbanking');
    }

}
