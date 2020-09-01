<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\PaperMandate\Entity;

class CreatePaperMandatesTable extends Migration
{
    const VARCHAR_LEN = 255;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAPER_MANDATE, function (Blueprint $table) {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::BANK_ACCOUNT_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->string(Entity::STATUS, self::VARCHAR_LEN);

            $table->string(Entity::UMRN, self::VARCHAR_LEN)
                  ->nullable();;

            $table->string(Entity::SPONSOR_BANK_CODE, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::UTILITY_CODE, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::DEBIT_TYPE, self::VARCHAR_LEN);

            $table->string(Entity::TYPE, self::VARCHAR_LEN);

            $table->string(Entity::FREQUENCY, self::VARCHAR_LEN);

            $table->string(Entity::REFERENCE_1, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::REFERENCE_2, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::SECONDARY_ACCOUNT_HOLDER, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::TERTIARY_ACCOUNT_HOLDER, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::GENERATED_FILE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::GENERATED_FORM_URL, self::VARCHAR_LEN)
                  ->nullable();

            $table->integer(Entity::GENERATED_FORM_URL_EXPIRE)
                  ->nullable();

            $table->string(Entity::UPLOADED_FILE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::TERMINAL_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::FORM_CHECKSUM, 14)
                  ->nullable();

            $table->integer(Entity::START_AT);

            $table->bigInteger(Entity::END_AT)
                  ->unsigned()
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::CUSTOMER_ID);

            $table->index(Entity::FORM_CHECKSUM);

            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paper_mandates');
    }
}
