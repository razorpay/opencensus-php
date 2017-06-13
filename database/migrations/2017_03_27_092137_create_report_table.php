<?php

use RZP\Constants\Table;

use RZP\Models\Report\Entity as Report;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\FileStore\Entity as FileStore;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::REPORT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Report::ID, Report::ID_LENGTH)
                  ->primary();

            $table->char(Report::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(Report::FILE_ID, FileStore::ID_LENGTH);

            $table->char(Report::TYPE);

            $table->integer(Report::GENERATED_AT)
                  ->unsigned()
                  ->nullable();

            $table->char(Report::GENERATED_BY);

            $table->integer(Report::START_TIME)
                  ->unsigned();

            $table->integer(Report::END_TIME)
                  ->unsigned();

            $table->integer(Report::DAY)
                  ->unsigned()
                  ->nullable();

            $table->integer(Report::MONTH)
                  ->unsigned();

            $table->integer(Report::YEAR)
                  ->unsigned();

            $table->integer(Report::CREATED_AT);

            $table->integer(Report::UPDATED_AT);

            // References Merchant Id
            $table->foreign(Report::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            // References FileStore Id
            $table->foreign(Report::FILE_ID)
                  ->references(FileStore::ID)
                  ->on(Table::FILE_STORE)
                  ->on_delete('restrict');

            $table->index(Report::CREATED_AT);

            $table->index(Report::TYPE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::REPORT, function($table)
        {
            $table->dropForeign(
                Table::REPORT . '_' . Report::MERCHANT_ID.'_foreign');

            $table->dropForeign(
                Table::REPORT . '_' . Report::FILE_ID.'_foreign');
        });

        Schema::drop(Table::REPORT);
    }
}
