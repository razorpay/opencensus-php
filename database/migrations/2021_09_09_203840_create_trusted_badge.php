<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\TrustedBadge\Entity as TrustedBadge;

class CreateTrustedBadge extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TRUSTED_BADGE, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(TrustedBadge::MERCHANT_ID, TrustedBadge::ID_LENGTH)
                ->primary();

            $table->string(TrustedBadge::STATUS, TrustedBadge::STATUS_LENGTH)
                ->nullable(false);

            $table->string(TrustedBadge::MERCHANT_STATUS, TrustedBadge::MERCHANT_STATUS_LENGTH)
                ->nullable(false)
                ->default('');

            $table->integer(TrustedBadge::CREATED_AT);

            $table->integer(TrustedBadge::UPDATED_AT);

            $table->index(TrustedBadge::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::TRUSTED_BADGE);
    }
}
