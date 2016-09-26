<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Base\UniqueIdEntity;

class CreateUpi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('upi', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->string('action');
            $table->string('amount');
            $table->string('bank');
            $table->string('contact')->nullable();
            $table->string('name')->nullable();
            $table->tinyInteger('received')->default(0);
            $table->string('gateway_merchant_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->string('email')->nullable();
            $table->string('status_code')->nullable();
            $table->string('vpa')->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');

            $table->index('received');
            $table->index('gateway_payment_id');
            $table->index('bank');
            $table->index('status_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('upi', function($table)
        {
            $table->dropForeign('upi_payment_id_foreign');
        });

        Schema::drop('upi');
    }
}
