<?php

use RZP\Constants\Table;
use RZP\Models\PaymentsUpi\Vpa\Entity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsUpiVpaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENTS_UPI_VPA, function (Blueprint $table) {

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            // Max size is 200 for NPCI, but we have never seen a vpa that long
            $column = $table->string(Entity::USERNAME, 200);
            // Laravel's method collate does not work
            $column->collation = 'utf8mb4_unicode_ci';

            $table->string(Entity::HANDLE, 50);

            $table->string(Entity::NAME, 100)
                  ->nullable();

            $table->unsignedInteger(Entity::CREATED_AT);
            $table->unsignedInteger(Entity::UPDATED_AT);

            $table->char(Entity::STATUS, 40)
                  ->nullable();

            $table->unsignedInteger(Entity::RECEIVED_AT)
                  ->nullable();

            $table->index(Entity::USERNAME);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PAYMENTS_UPI_VPA);
    }
}
