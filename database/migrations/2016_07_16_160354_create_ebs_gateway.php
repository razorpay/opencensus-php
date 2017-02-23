<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


use RZP\Constants\Table;
use RZP\Gateway\Ebs\Entity as Ebs;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;

class CreateEbsGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::EBS, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Ebs::ID);

            $table->string(Ebs::PAYMENT_ID, UniqueIdEntity::ID_LENGTH);

            $table->string(Ebs::TRANSACTION_ID)->nullable();

            $table->string(Ebs::ACTION);

            $table->string(Ebs::REQUEST_ID)->nullable();

            $table->string(Ebs::GATEWAY_PAYMENT_ID)->nullable();

            $table->string(Ebs::REFUND_ID, UniqueIdEntity::ID_LENGTH)->nullable();

            $table->tinyInteger(Ebs::RECEIVED)->default(0);

            $table->tinyInteger(Ebs::IS_FLAGGED)->default(0);

            $table->integer(Ebs::AMOUNT);

            $table->string(Ebs::ERROR_CODE)->nullable();

            $table->string(Ebs::ERROR_DESCRIPTION)->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Ebs::CREATED_AT);

            $table->integer(Ebs::UPDATED_AT);

            $table->foreign(Ebs::PAYMENT_ID)
                  ->references(Payment\Entity::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->index(Ebs::RECEIVED);

            $table->index(Ebs::PAYMENT_ID);

            $table->index(Ebs::GATEWAY_PAYMENT_ID);

            $table->index(Ebs::REFUND_ID);

            $table->index(Ebs::IS_FLAGGED);

            $table->index(Ebs::CREATED_AT);

            $table->index(Ebs::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::EBS, function($table)
        {
            $table->dropForeign('ebs_payment_id_foreign');
        });

        Schema::drop(Table::EBS);
    }

}
