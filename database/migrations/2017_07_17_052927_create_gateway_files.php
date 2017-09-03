<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Gateway\File\Entity as GatewayFile;
use RZP\Models\FileStore\Entity as FileStore;

class CreateGatewayFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::GATEWAY_FILE, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(GatewayFile::ID, GatewayFile::ID_LENGTH)
                  ->primary();

            $table->string(GatewayFile::SOURCE, 50);

            $table->string(GatewayFile::TYPE, 20);

            $table->string(GatewayFile::SENDER);

            $table->text(GatewayFile::RECIPIENTS)
                  ->nullable();

            $table->integer(GatewayFile::FROM);

            $table->integer(GatewayFile::TO);

            $table->string(GatewayFile::STATUS, 20)
                  ->default('created');

            $table->string(GatewayFile::FAILURE_CODE, 50)
                  ->nullable();

            $table->tinyInteger(GatewayFile::SCHEDULED)
                  ->default(1);

            $table->tinyInteger(GatewayFile::PARTIALLY_PROCESSED)
                  ->default(0);

            $table->integer(GatewayFile::ATTEMPTS)
                  ->default(1);

            $table->integer(GatewayFile::FAILED_AT)
                  ->nullable();

            $table->integer(GatewayFile::FILE_GENERATED_AT)
                  ->nullable();

            $table->integer(GatewayFile::SENT_AT)
                  ->nullable();

            $table->integer(GatewayFile::ACKNOWLEDGED_AT)
                  ->nullable();

            $table->integer(GatewayFile::CREATED_AT);

            $table->integer(GatewayFile::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::GATEWAY_FILE, function ($table)
        {
            $table->dropForeign(
                Table::GATEWAY_FILE . '_' . GatewayFile::FILE_ID . '_foreign');
        });

        Schema::drop(Table::GATEWAY_FILE);
    }
}
