<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stakeholders', function (Blueprint $table) {
            $table->char('id', 14)->collation('utf8_bin');
            $table->char('merchant_id', 14)->collation('utf8_bin');
            $table->string('email', 255)->collation('utf8_bin')->nullable();
            $table->string('name', 255)->collation('utf8_bin')->nullable();
            $table->string('phone_primary', 255)->collation('utf8_bin')->nullable();
            $table->string('phone_secondary', 255)->collation('utf8_bin')->nullable();
            $table->tinyInteger('director')->nullable();
            $table->tinyInteger('executive')->nullable();
            $table->unsignedInteger('percentage_ownership')->nullable();
            $table->string('poi_identification_number', 255)->collation('utf8_bin')->nullable();
            $table->string('poi_status', 255)->collation('utf8_bin')->nullable();
            $table->string('poa_status', 255)->collation('utf8_bin')->nullable();
            $table->text('notes')->collation('utf8_bin')->nullable();
            $table->integer('created_at')->nullable(false);
            $table->integer('updated_at')->nullable(false);
            $table->integer('deleted_at')->nullable()->default(null);
            $table->string('pan_doc_status', 30)->collation('utf8_bin')->nullable();
            $table->string('aadhaar_esign_status', 30)->collation('utf8_bin')->nullable();
            $table->string('aadhaar_pin', 30)->collation('utf8_bin')->nullable();
            $table->tinyInteger('aadhaar_linked')->default(1);
            $table->string('aadhaar_verification_with_pan_status', 30)->collation('utf8_bin')->nullable();
            $table->string('bvs_probe_id', 30)->collation('utf8_bin')->nullable();
            $table->char('audit_id', 14)->collation('utf8mb4_bin')->nullable()->default(null);
            $table->json('verification_metadata')->nullable();

            $table->primary('id');
            $table->index('created_at', 'stakeholders_created_at_index');
            $table->index('updated_at', 'stakeholders_updated_at_index');
            $table->index('merchant_id', 'stakeholders_merchant_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stakeholders');
    }
};
