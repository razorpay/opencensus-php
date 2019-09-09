<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Gateway\File\Entity as GatewayFile;

class CreateQuboleGatewayFilesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            GatewayFile::ID,
            GatewayFile::TYPE,
            GatewayFile::TARGET,
            GatewayFile::SUB_TYPE,
            GatewayFile::BEGIN,
            GatewayFile::END,
            GatewayFile::STATUS,
            GatewayFile::PROCESSING,
            GatewayFile::PARTIALLY_PROCESSED,
            GatewayFile::COMMENTS,
            GatewayFile::SCHEDULED,
            GatewayFile::ATTEMPTS,
            GatewayFile::ERROR_CODE,
            GatewayFile::ERROR_DESCRIPTION,
            GatewayFile::FILE_GENERATED_AT,
            GatewayFile::SENT_AT,
            GatewayFile::ACKNOWLEDGED_AT,
            GatewayFile::FAILED_AT,
            GatewayFile::CREATED_AT,
            GatewayFile::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_gateway_files_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::GATEWAY_FILE;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_gateway_files_view');
    }
}
