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

            $table->string(GatewayFile::TYPE, 20);

            $table->string(GatewayFile::TARGET, 50);

            $table->string(GatewayFile::SUB_TYPE, 25)
                  ->nullable();

            $table->string(GatewayFile::SENDER, 100);

            $table->text(GatewayFile::RECIPIENTS)
                  ->nullable();

            $table->integer(GatewayFile::BEGIN);

            $table->integer(GatewayFile::END);

            $table->string(GatewayFile::STATUS, 20)
                  ->default('created');

            $table->tinyInteger(GatewayFile::PROCESSING)
                  ->default(0);

            $table->tinyInteger(GatewayFile::PARTIALLY_PROCESSED)
                  ->default(0);

            $table->string(GatewayFile::COMMENTS)
                  ->nullable();

            $table->tinyInteger(GatewayFile::SCHEDULED)
                  ->default(1);

            $table->integer(GatewayFile::ATTEMPTS)
                  ->default(1);

            $table->string(GatewayFile::ERROR_CODE, 50)
                  ->nullable();

            $table->string(GatewayFile::ERROR_DESCRIPTION)
                  ->nullable();

            $table->integer(GatewayFile::FILE_GENERATED_AT)
                  ->nullable();

            $table->integer(GatewayFile::SENT_AT)
                  ->nullable();

            $table->integer(GatewayFile::ACKNOWLEDGED_AT)
                  ->nullable();

            $table->integer(GatewayFile::FAILED_AT)
                  ->nullable();

            $table->integer(GatewayFile::CREATED_AT);

            $table->integer(GatewayFile::UPDATED_AT);

            $table->index(GatewayFile::TARGET);
            $table->index(GatewayFile::TYPE);
            $table->index(GatewayFile::STATUS);
            $table->index(GatewayFile::CREATED_AT);
            $table->index(GatewayFile::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::GATEWAY_FILE);
    }
}
