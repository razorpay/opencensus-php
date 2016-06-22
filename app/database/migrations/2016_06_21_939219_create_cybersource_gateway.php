<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Gateway\Cybersource;
use Models\Base\UniqueIdEntity;

class CreateCybersourceGateway extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Constants\Table::CYBERSOURCE, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);

            $table->char('action', 10)->nullable();

            $table->integer('received')->default(0);

            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();

            $table->char('auth_data', 40)->nullable();

            $table->char('commerce_indicator', 20)->nullable();

            $table->integer('amount');

            $table->char('pares_status', 20)->nullable();

            $table->char('status', 20);

            $table->char('xid', 40)->nullable();

            $table->char('eci', 20)->nullable();

            $table->char('cavv', 40)->nullable();
            
            $table->char('ref', 120)->nullable();
            
            $table->char('capture_ref', 30)->nullable();
            
            $table->integer('error_code')->nullable();
            
            $table->char('error_text',30)->nullable();
            
            $table->integer('created_at');
            
            $table->integer('updated_at');
            
            $table->char('collection_indicator', 20)->nullable();

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');

            $table->index('status');
        
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
        Schema::table(Constants\Table::CYBERSOURCE, function($table)
        {
            $table->dropForeign('cybersource_payment_id_foreign');
        });

        Schema::drop('cybersource');
    }

}
