<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\PaperMandate\PaperMandateUpload\Entity;

class CreatePaperMandateUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAPER_MANDATE_UPLOAD, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::PAPER_MANDATE_ID, Entity::ID_LENGTH);

            $table->char(Entity::UPLOADED_FILE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::ENHANCED_FILE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::STATUS, 40);

            $table->text(Entity::EXTRACTED_RAW_DATA)
                  ->nullable();

            $table->text(Entity::NOT_MATCHING)
                  ->nullable();

            $table->float(Entity::TIME_TAKEN_TO_PROCESS, 32)
                  ->nullable();

            $table->string(Entity::UMRN, 255)
                  ->nullable();
            $table->string(Entity::NACH_DATE, 255)
                  ->nullable();
            $table->string(Entity::SPONSOR_CODE, 255)
                  ->nullable();
            $table->string(Entity::UTILITY_CODE, 255)
                  ->nullable();
            $table->string(Entity::BANK_NAME, 255)
                  ->nullable();
            $table->string(Entity::ACCOUNT_TYPE, 255)
                  ->nullable();
            $table->string(Entity::IFSC_CODE, 255)
                  ->nullable();
            $table->string(Entity::MICR, 255)
                  ->nullable();
            $table->string(Entity::COMPANY_NAME, 255)
                  ->nullable();
            $table->string(Entity::FREQUENCY, 255)
                  ->nullable();
            $table->string(Entity::AMOUNT_IN_NUMBER, 255)
                  ->nullable();
            $table->string(Entity::AMOUNT_IN_WORDS, 255)
                  ->nullable();
            $table->string(Entity::DEBIT_TYPE, 255)
                  ->nullable();
            $table->string(Entity::START_DATE, 255)
                  ->nullable();
            $table->string(Entity::END_DATE, 255)
                  ->nullable();
            $table->string(Entity::UNTIL_CANCELLED, 255)
                  ->nullable();
            $table->string(Entity::NACH_TYPE, 255)
                  ->nullable();
            $table->string(Entity::PHONE_NUMBER, 255)
                  ->nullable();
            $table->string(Entity::EMAIL_ID, 255)
                  ->nullable();
            $table->string(Entity::REFERENCE_1, 255)
                  ->nullable();
            $table->string(Entity::REFERENCE_2, 255)
                  ->nullable();
            $table->string(Entity::SIGNATURE_PRESENT_PRIMARY, 255)
                  ->nullable();
            $table->string(Entity::SIGNATURE_PRESENT_SECONDARY, 255)
                  ->nullable();
            $table->string(Entity::SIGNATURE_PRESENT_TERTIARY, 255)
                  ->nullable();
            $table->string(Entity::PRIMARY_ACCOUNT_HOLDER, 255)
                  ->nullable();
            $table->string(Entity::SECONDARY_ACCOUNT_HOLDER, 255)
                  ->nullable();
            $table->string(Entity::TERTIARY_ACCOUNT_HOLDER, 255)
                  ->nullable();
            $table->string(Entity::ACCOUNT_NUMBER, 255)
                  ->nullable();
            $table->string(Entity::FORM_CHECKSUM, 255)
                  ->nullable();
            $table->string(Entity::STATUS_REASON, 255)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::MERCHANT_ID);
            $table->index(Entity::PAPER_MANDATE_ID);
            $table->index(Entity::STATUS);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PAPER_MANDATE_UPLOAD);
    }
}
