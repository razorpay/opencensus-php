<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropAdminLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::drop('admin_leads');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('admin_leads', function (Blueprint $table) {
            $table->char('id', 14)->primary();

            $table->string('admin_id', 250);

            $table->string('token', 250)->unique();

            $table->string('email', 250);

            $table->text('form_data');

            $table->integer('created_at');
            $table->integer('updated_at');
        });
    }
}
