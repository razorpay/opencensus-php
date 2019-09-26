<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Terminal;
use RZP\Models\TerminalOnboardingDetail\Entity;

class CreateTerminalOnboardingDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terminal_onboarding_details', function (Blueprint $table)
            {
                $table->engine = 'InnoDB';

                $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

                $table->char(Terminal\Entity::TERMINAL_ID, Terminal\Entity::ID_LENGTH);

                $table->char(Entity::STATUS, 255);
    
                $table->boolean(Entity::RETRY)
                ->default(0);

                $table->char(Entity::ERROR_CODE, 100)
                ->nullable();

                $table->text(Entity::ERROR_DESCRIPTION)
                ->nullable();

                $table->tinyInteger(Entity::ATTEMPTS)
                ->default(0);

                $table->tinyInteger(Entity::VERIFY_BUCKET)
                ->default(0);

                $table->integer(Entity::VERIFY_AT)
                ->unsigned()
                ->nullable();  
                
                $table->integer(Entity::CREATED_AT);

                $table->integer(Entity::UPDATED_AT);    

                $table->index(Entity::STATUS);

                $table->index(Entity::ERROR_CODE);

                $table->index(Entity::RETRY);

                $table->index(Entity::ATTEMPTS);

                $table->index(Entity::VERIFY_BUCKET);

            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('terminal_onboarding_details');
    }
}


