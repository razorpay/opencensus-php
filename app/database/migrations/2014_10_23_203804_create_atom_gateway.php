<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Gateway\Hdfc;
use Models\Base\UniqueIdEntity;

class CreateAtomGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('atom', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('id', UniqueIdEntity::ID_LENGTH);

            $table->string('gateway_payment_id');

            $table->string('token');

            $table->boolean('success')
                  ->nullable();

            $table->string('bank_code')
                  ->nullable();

            $table->string('bank_name')
                  ->nullable();

            $table->string('bank_transaction_id')
                  ->nullable();

            $table->text('callback_data')
                  ->nullable();

            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('id')
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
        Schema::table('atom', function($table)
        {
            $table->dropForeign('atom_id_foreign');
        });

        Schema::drop('atom');
    }
}