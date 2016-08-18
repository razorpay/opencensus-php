<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvitationsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invitations', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('merchant_id', 14);
            $table->char('user_id', 14)->nullable();

            $table->string('email');
            $table->string('token', 40)->unique();
            $table->string('role');

            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('merchant_id')
                  ->references('id')
                  ->on('merchants');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invitations', function(Blueprint $table)
        {
            $table->dropForeign('invitations_merchant_id_foreign');
            $table->dropForeign('invitations_user_id_foreign');
        });

        Schema::drop('invitations');
    }

}
