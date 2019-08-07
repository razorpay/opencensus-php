<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\FileStore\Entity as FileStore;

class CreateQuboleFilesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            FileStore::ID,
            FileStore::MERCHANT_ID,
            FileStore::TYPE,
            FileStore::ENTITY_ID,
            FileStore::ENTITY_TYPE,
            FileStore::COMMENTS,
            FileStore::EXTENSION,
            FileStore::MIME,
            FileStore::SIZE,
            FileStore::STORE,
            FileStore::BUCKET,
            FileStore::REGION,
            FileStore::PERMISSION,
            FileStore::ENCRYPTION_METHOD,
            FileStore::PASSWORD,
            FileStore::METADATA,
            FileStore::CREATED_AT,
            FileStore::UPDATED_AT,
            FileStore::DELETED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_files_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::FILE_STORE;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_files_view');
    }
}
