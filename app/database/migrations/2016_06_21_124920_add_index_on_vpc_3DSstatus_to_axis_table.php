<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Gateway\AxisMigs;

class AddIndexOnVpc3DSstatusToAxisTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('axis', function ($table)
		{
			$table->index('vpc_3DSstatus');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('axis', function ($table)
		{
			$table->dropIndex('axis_vpc_3DSstatus_index');
		});
	}

}
