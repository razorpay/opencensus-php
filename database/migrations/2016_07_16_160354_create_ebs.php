<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Gateway\Ebs\Entity as Ebs;
use RZP\Models\Base\UniqueIdEntity;

class CreateEbs extends Migration {

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

            $table->char(Ebs::REFUND_PAYMENT_ID, UniqueIdEntity::ID_LENGTH);

            $table->string(Ebs::TXN_AMOUNT);

            $table->string(Ebs::STATUS);

            $table->string(Ebs::CURRENCY);

            $table->string(Ebs::ACTION);

            $table->string(Ebs::REQUEST_ID)->nullable();

            $table->string(Ebs::MODE)->nullable();

            $table->string(Ebs::PAYMENT_MODE)->nullable();

            $table->string(Ebs::CHANNEL)->nullable();

            $table->boolean(Ebs::RECEIVED)->default(0);

            $table->string(Ebs::ERROR_CODE)->nullable();

            $table->string(Ebs::ERROR_DESCRIPTION)->nullable();

            $table->string(Ebs::REF_AMOUNT)->nullable();

            $table->string(Ebs::TRANSACTION_ID)->nullable();

            $table->string(Ebs::EBS_PAYMENT_ID)->nullable();

            $table->string(Ebs::REF_STATUS)->nullable();

            $table->string(Ebs::AUTH_STATUS)->nullable();

            $table->string(Ebs::REFUND_ID, UniqueIdEntity::ID_LENGTH)->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Ebs::CREATED_AT);

            $table->integer(Ebs::UPDATED_AT);

            $table->foreign(Ebs::PAYMENT_ID)
                  ->references(Ebs::ID)
                  ->on('payments')
                  ->on_delete('restrict');

            $table->index(Ebs::RECEIVED);
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
