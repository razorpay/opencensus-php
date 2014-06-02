<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHashes extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		// Schema::create('hashes', function(Blueprint $table){
    //         $table->engine = 'InnoDB';
		//
    //         $table->string('hash', 64)
    //               ->primary();
    //
    //         // Adds created_at and updated_at columns to the table
    //         $table->integer('created_at');
    //         $table->integer('updated_at');
    //     });
		Schema::dropIfExists('hashes');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('hashes');
	}

}
