<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Payout\Entity as Payout;

class CreatePsSourceRequestIdMapping extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_source_request_id_mapping', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('id', 14)->primary();
            $table->string('source_type', 255);
            $table->char('source_id', 14);
            $table->string('request_id', 255);
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index(['source_type', 'source_id']);
            $table->index(['source_type', 'request_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_source_request_id_mapping');
    }
}
