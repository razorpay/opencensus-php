<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Card;

class EditIins extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
  public function up()
  {
    Schema::table(Table::IIN, function(Blueprint $table)
    {
      $table->boolean(Card\Detail::INTERNATIONAL)
            ->nullable();
    });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    //
  }

}
