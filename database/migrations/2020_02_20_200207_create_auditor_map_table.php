<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuditorMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auditor_map', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('auditor_id', 14);

            $table->string('auditor_type', 255); // types of auditors like reviewer, approver etc.

            $table->char('entity_id', 14);

            $table->string('entity_type', 255); // entities like banking_accounts

            $table->unique(['auditor_id', 'auditor_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auditor_map');
    }
}
