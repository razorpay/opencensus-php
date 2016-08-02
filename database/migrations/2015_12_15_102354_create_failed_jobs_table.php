<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFailedJobsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('failed_jobs', function(Blueprint $table)
		{
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->text('connection');
			$table->text('queue');
			$table->text('payload');

            /**
             * Workaround Known issue in L4
             * @see http://stackoverflow.com/q/18067614/368328
             */
			$table->timestamp('failed_at')->default(DB::raw('CURRENT_TIMESTAMP'));

		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('failed_jobs');
	}

}
