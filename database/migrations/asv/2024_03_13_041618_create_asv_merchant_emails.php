<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_emails', function (Blueprint $table) {
            $table->char('id', 14)->primary();
            $table->string('type', 255);
            $table->text('email')->nullable();;
            $table->string('phone', 255)->nullable();
            $table->string('policy', 255)->nullable();
            $table->string('url', 255)->nullable();
            $table->char('merchant_id', 14);
            $table->tinyInteger('verified')->default(0);
            $table->integer('created_at');
            $table->integer('updated_at');

            // Indexes
            $table->unique(['merchant_id', 'type']);
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('merchant_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_emails');
    }
};
