<?php

    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Migrations\Migration;
    use Models\Base\UniqueIdEntity;

    class CreateMobikwik extends Migration
    {

        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create( 'mobikwik', function ( Blueprint $table ) {
                $table->engine = 'InnoDB';

                $table->increments( 'id' );

                $table->char( 'payment_id', UniqueIdEntity::ID_LENGTH );
                $table->string( 'action' );
                $table->string( 'method' );


                $table->string( 'email' );
                $table->string( 'txnamount' )->nullable();
                $table->string( 'cell' )->nullable();
                $table->string( 'orderid', 25 )->nullable();
                $table->string( 'mid', 25 )->nullable();
                $table->string( 'merchantname')->nullable();
                $table->string( 'showmobile')->nullable();

                $table->string( 'statuscode')->nullable();
                $table->string( 'statusmessage')->nullable();
                $table->string( 'refid')->nullable();

                $table->char( 'refund_id', UniqueIdEntity::ID_LENGTH )->nullable();

                // Adds created_at and updated_at columns to the table
                $table->integer( 'created_at' );
                $table->integer( 'updated_at' );

                $table->foreign( 'payment_id' )
                    ->references( 'id' )
                    ->on( 'payments' )
                    ->on_delete( 'restrict' );
            } );
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::table('mobikwik', function($table)
            {
                $table->dropForeign('mobikwik_payment_id_foreign');
            });

            Schema::drop('mobikwik');
        }

    }
