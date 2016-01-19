<?php

use Constants\Table;
use Models\Emi;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmiPlansTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::EMI_PLANS, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Emi\Entity::ID, 14)
                  ->primary();

            $table->char(Emi\Entity::BANK, 4);

            $table->integer(Emi\Entity::RATE);

            $table->integer(Emi\Entity::DURATION);
            
            $table->string(Emi\Entity::METHODS)
            	  ->nullable();

            $table->integer(Emi\Entity::MIN_AMOUNT);

            $table->integer(Emi\Entity::CREATED_AT);
            $table->integer(Emi\Entity::UPDATED_AT);
            $table->integer(Emi\Entity::DELETED_AT)
                  ->nullable();
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::EMI_PLANS);
    }
}
