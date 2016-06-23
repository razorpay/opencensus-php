<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Gateway\Cybersource;
use Models\Base\UniqueIdEntity;
use Constants;

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

            $table->char(Cybersource\Entity::PAYMENT_ID, UniqueIdEntity::ID_LENGTH);

            $table->char(Cybersource\Entity::ACTION, 10)->nullable();

            $table->integer(Cybersource\Entity::RECEIVED)->default(0);

            $table->char(Cybersource\Entity::REFUND_ID, UniqueIdEntity::ID_LENGTH)->nullable();

            $table->char(Cybersource\Entity::AUTH_DATA, 40)->nullable();

            $table->char(Cybersource\Entity::COMMERCE_INDICATOR, 20)->nullable();

            $table->integer(Cybersource\Entity::AMOUNT);

            $table->char(Cybersource\Entity::PARES_STATUS, 20)->nullable();

            $table->char(Cybersource\Entity::STATUS, 20);

            $table->char(Cybersource\Entity::XID, 40)->nullable();

            $table->char(Cybersource\Entity::ECI, 20)->nullable();

            $table->char(Cybersource\Entity::CAVV, 40)->nullable();
            
            $table->char(Cybersource\Entity::REF, 120)->nullable();
            
            $table->char(Cybersource\Entity::CAPTURE_REF, 30)->nullable();
            
            $table->integer(Cybersource\Entity::ERROR_CODE)->nullable();
            
            $table->integer(Cybersource\Entity::CREATED_AT);
            
            $table->integer(Cybersource\Entity::UPDATED_AT);
            
            $table->char(Cybersource\Entity::COLLECTION_INDICATOR, 20)->nullable();

            $table->foreign(Cybersource\Entity::PAYMENT_ID)
                  ->references(Cybersource\Entity::ID)
                  ->on('payments')
                  ->on_delete('restrict');

            $table->index(Cybersource\Entity::STATUS);
        
            $table->index(Cybersource\Entity::RECEIVED);

            $table->index(Cybersource\Entity::CREATED_AT);

            $table->index(Cybersource\Entity::REFUND_ID);
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

        Schema::drop(Constants\Table::CYBERSOURCE);
    }

}
