<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;

use RZP\Models\Base\PublicEntity;

class CreateSettingsTable extends Migration
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $keyColumn;

    /**
     * @var string
     */
    protected $valueColumn;

	public function __construct()
	{
        $this->tableName   = Config::get('settings.table');
        $this->keyColumn   = Config::get('settings.keyColumn');
        $this->valueColumn = Config::get('settings.valueColumn');
	}

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create($this->tableName, function(Blueprint $table)
		{
			$table->increments('id');

			$table->string('entity_type', 100);

			$table->char('entity_id', PublicEntity::ID_LENGTH);

			$table->string('module', 100);

			$table->string($this->keyColumn)
                  ->index();

			$table->text($this->valueColumn);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop($this->tableName);
	}
}
