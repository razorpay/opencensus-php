<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdminRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_roles', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('admin_id', 14);
            $table->char('role_id', 14);

            $table->unique(['admin_id', 'role_id']);

            $table->foreign('admin_id')
                  ->references('id')
                  ->on('admins');

            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('admin_roles');
    }
}
