<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;

use RZP\Models\Base\PublicEntity;

class CreateSettingsTable extends Migration
{
    const ENTITY_TYPE = 'entity_type';
    const ENTITY_ID   = 'entity_id';
    const MODULE      = 'module';
    const CREATED_AT  = 'created_at';
    const UPDATED_AT  = 'updated_at';

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
            $table->increments(PublicEntity::ID);

            $table->string(self::ENTITY_TYPE, 100);

            $table->char(self::ENTITY_ID, PublicEntity::ID_LENGTH);

            $table->string(self::MODULE, 100);

            $table->string($this->keyColumn);

            $table->text($this->valueColumn);

            $table->integer(self::CREATED_AT);

            $table->integer(self::UPDATED_AT);

            $table->index(self::ENTITY_TYPE);

            $table->index(self::ENTITY_ID);

            $table->index(self::MODULE);

            $table->index($this->keyColumn);

            $table->index(self::CREATED_AT);

            $table->index(self::UPDATED_AT);
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
