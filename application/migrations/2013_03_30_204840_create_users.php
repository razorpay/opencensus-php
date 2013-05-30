<?php

class Create_Users {

  /**
   * Make changes to the database.
   *
   * @return void
   */
  public function up()
  {
    Schema::create('users', function($table){
      $table->engine = 'InnoDB';
      
      $table->increments('id');

      $table->string('name', 50)
            ->nullable();

      $table->string('email', 256)
            ->unique();

      $table->string('password');
    });
  }

  /**
   * Revert the changes to the database.
   *
   * @return void
   */
  public function down()
  {
    Schema::drop('users');
  }

}