<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

class CreateAdminTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADMIN_TOKEN, function (Blueprint $table)
        {
            $table->increments('id');

            $table->char('admin_id', 14);

            $table->string('token', 250)->unique();

            $table->integer('created_at');
            $table->integer('updated_at');
            $table->integer('expires_at')->nullable();

            $table->foreign('admin_id')
                  ->references('id')
                  ->on('admins');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::ADMIN_TOKEN);
    }
}
