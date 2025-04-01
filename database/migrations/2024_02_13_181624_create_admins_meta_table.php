<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Admin\AdminsMeta\Entity as AdminsMeta;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADMINS_META, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(AdminsMeta::ID, AdminsMeta::ID_LENGTH)
                ->primary();

            $table->char(AdminsMeta::ADMIN_ID, AdminsMeta::ID_LENGTH);

            $table->char(AdminsMeta::UNIQUE_IDENTIFIER);

            $table->string(AdminsMeta::AUTH_MODE);

            $table->integer(AdminsMeta::USER_DISABLED_AT)
                ->unsigned()
                ->nullable();

            $table->integer(AdminsMeta::CREATED_AT)
                ->unsigned();

            $table->integer(AdminsMeta::UPDATED_AT)
                ->unsigned();

            $table->integer(AdminsMeta::DELETED_AT)
                ->unsigned()
                ->nullable();

            $table->string(AdminsMeta::DISABLED_REASON)
                ->nullable();

            $table->index(AdminsMeta::UNIQUE_IDENTIFIER);
            $table->index(AdminsMeta::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ADMINS_META, function ($table) {
            $table->dropForeign(Table::ADMINS_META . '_' . AdminsMeta::ADMIN_ID . '_foreign');
        });
        Schema::dropIfExists('admins_meta');
    }
};
