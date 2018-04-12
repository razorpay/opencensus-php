<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Base\UniqueIdEntity;

class CreateHdfcResponseXml extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hdfc_response_xml', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);

            $table->text('enroll')
                  ->nullable();

            $table->text('auth_enrolled')
                  ->nullable();

            $table->text('auth_not_enrolled')
                  ->nullable();

            $table->text('capture')
                  ->nullable();

            $table->text('refund')
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index('payment_id');
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
        Schema::drop('hdfc_response_xml');
    }

}
