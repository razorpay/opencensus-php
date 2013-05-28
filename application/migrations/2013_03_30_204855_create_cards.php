<?php

class Create_Cards {

  /**
   * Make changes to the database.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('cards', function($table){
      $table->engine = 'InnoDB';
      
      $table->increments('id');
      $table->string('number');
      $table->integer('expiry_month')
            ->unsigned()
            ->nullable();
      $table->integer('expiry_year')
            ->unsigned()
            ->nullable();
      $table->integer('cvv')
            ->unsigned()
            ->nullable();
      $table->integer('cardtype_id')
            ->unsigned()
            ->nullable();
      $table->string('name');
      $table->string('address_line1')->nullable();
      $table->string('address_line2')->nullable();
      $table->string('address_state')->nullable();
      $table->integer('address_zip')
            ->unsigned()
            ->nullable();
      $table->string('address_country')->nullable();
      $table->integer('user_id')->unsigned()->nullable();
      $table->timestamps();

      $table->foreign('cardtype_id')->references('id')->on('cardtypes')->on_delete('SET NULL');
      $table->foreign('user_id')->references('id')->on('users')->on_delete('SET NULL');
    });
  }

  /**
   * Revert the changes to the database.
   *
   * @return void
   */
  public function down()
  {
    Schema::drop('cards');
  }

}