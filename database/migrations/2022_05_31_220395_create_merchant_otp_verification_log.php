<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Product\Otp\Entity;

class CreateMerchantOtpVerificationLog extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_OTP_VERIFICATION_LOGS, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();
            $table->string(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::EXTERNAL_REFERENCE_NUMBER, 50)
                ->nullable();

            $table->string(Entity::CONTACT_MOBILE, 255);

            $table->boolean(Entity::RAZORPAY_VERIFIED)
                  ->default(false);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::CREATED_AT);


            $table->integer(Entity::OTP_SUBMISSION_TIMESTAMP)
                    ->nullable();
            $table->integer(Entity::OTP_VERIFICATION_TIMESTAMP)
                    ->nullable();

            $table->index([Entity::MERCHANT_ID, Entity::CONTACT_MOBILE]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MERCHANT_OTP_VERIFICATION_LOGS);
    }
}
