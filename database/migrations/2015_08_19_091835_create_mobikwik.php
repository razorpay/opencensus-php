<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Base\UniqueIdEntity;

class CreateMobikwik extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobikwik', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->increments('id');
// base fields
            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->string('action');
            $table->string('method');
            $table->tinyInteger('received')->default(0);
// request params
            $table->string('email');
            $table->string('amount');
            $table->string('cell')->nullable();
            $table->char('orderid', UniqueIdEntity::ID_LENGTH)->nullable();
            $table->char('txid', UniqueIdEntity::ID_LENGTH)->nullable();
            $table->string('mid', 25)->nullable();
            $table->string('merchantname', 255)->nullable();
            $table->string('showmobile', 4)->nullable();
// response params
            $table->string('statuscode', 3)->nullable();
            $table->string('statusmessage')->nullable();
            $table->string('refid')->nullable();
            $table->string('ispartial', 3)->nullable();
// rzp refund id
            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();
// timestamps
            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');
            $table->index('received');
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->on_delete('restrict');

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mobikwik', function ($table) {
            $table->dropForeign('mobikwik_payment_id_foreign');
        });

        Schema::drop('mobikwik');
    }

}
