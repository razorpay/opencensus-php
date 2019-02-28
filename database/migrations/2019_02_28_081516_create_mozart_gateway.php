<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Mozart\Entity as Mozart;
use RZP\Constants\Table;
class CreateMozartGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create(Table::MOZART, function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->char(Mozart::ID)
                ->primary();
            $table->char(Mozart::PAYMENT_ID,Payment::ID_LENGTH);
            $table->char(Mozart::ACTION)
                ->nullable();
            $table->integer(Mozart::AMOUNT);
            $table->integer(Mozart::RECEIVED)
                ->default(0);
            $table->char(Mozart::BANK);
            $table->integer(Mozart::CREATED_AT);
            $table->integer(Mozart::UPDATED_AT);
            $table->integer(Mozart::DELETED_AT)
                ->unsigned()
                ->nullable();
            $table->json(Mozart::RAW)
                ->nullable();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MOZART);
    }
}