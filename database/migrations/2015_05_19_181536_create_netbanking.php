<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Base\UniqueIdEntity;

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
            $table->tinyInteger('received')->default(0);
            $table->string('client_code')->nullable();
            $table->string('merchant_code')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('bank_payment_id')->nullable();
            $table->bigInteger('int_payment_id')->nullable();
            $table->string('status')->nullable();
            $table->string('error_message')->nullable();
            $table->string('reference1')->nullable();
            $table->string('verification_id')->nullable();
            $table->string('si_token')->nullable();
            $table->string('si_status')->nullable();
            $table->string('si_message')->nullable();
            $table->string('date')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_subtype')->nullable();
            $table->string('account_branch_code')->nullable();
            $table->string('credit_account_number')->nullable();

            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();
            $table->char('caps_payment_id', UniqueIdEntity::ID_LENGTH);

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');

            $table->index('received');
            $table->index('int_payment_id');
            $table->index('caps_payment_id');
            $table->index('bank_payment_id');
            $table->index('verification_id');
            $table->index('refund_id');
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
        Schema::table('netbanking', function($table)
        {
            $table->dropForeign('netbanking_payment_id_foreign');
        });

        Schema::drop('netbanking');
    }

}
