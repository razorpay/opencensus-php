<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Gateway\Ebs;
use RZP\Models\Base\UniqueIdEntity;

class CreateEbs extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ebs', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->string('action');
            $table->string('TxnAmount');
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('ship_name')->nullable();
            $table->string('ship_address')->nullable();
            $table->string('ship_state')->nullable();
            $table->string('ship_city')->nullable();
            $table->string('ship_postal_code')->nullable();
            $table->string('ship_country')->nullable();
            $table->string('ship_phone')->nullable();
            $table->string('description')->nullable();
            $table->string('currency');
            $table->string('mode')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('channel')->nullable();
            $table->boolean('received')->default(0);
            $table->string('ReferenceNo')->nullable();

            $table->string('SecurityType')->nullable();
            $table->string('TxnDate')->nullable();
            $table->string('TxnReferenceNo')->nullable();
            $table->string('AuthStatus')->nullable();
            $table->string('SettlementType')->nullable();
            $table->string('ErrorStatus')->nullable();
            $table->string('ErrorDescription')->nullable();
            $table->string('RequestType')->nullable();
            $table->string('RefAmount')->nullable();
            $table->string('RefDateTime')->nullable();
            $table->string('RefStatus')->nullable();
            $table->string('RefundId')->nullable();
            $table->string('ErrorCode')->nullable();
            $table->string('ErrorReason')->nullable();
            $table->string('ProcessStatus')->nullable();
    
            $table->string('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');

            $table->index('received');
            $table->index('AuthStatus');
            $table->index('TxnReferenceNo');
            $table->index('RefundId');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ebs', function($table)
        {
            $table->dropForeign('ebs_payment_id_foreign');
        });

        Schema::drop('ebs');
    }

}
