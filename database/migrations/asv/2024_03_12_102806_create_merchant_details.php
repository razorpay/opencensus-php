<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_details', function (Blueprint $table) {
            $table->char('merchant_id', 14)->charset('utf8')->collation('utf8_bin')->primary();
            $table->string('contact_name', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('contact_email', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('contact_mobile', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('contact_landline', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_type', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_name', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_description', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_dba', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_website', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('additional_websites')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->tinyInteger('business_international')->default(0);
            $table->string('business_paymentdetails', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_registered_address', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_registered_address_l2', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_registered_state', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_registered_city', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_registered_district', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_registered_pin', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_registered_country', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_operation_address', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_operation_address_l2', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_operation_state', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_operation_city', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_operation_district', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_operation_pin', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_operation_country', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_doe', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('gstin', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('p_gstin', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('company_cin', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('company_pan', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('company_pan_name', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_category', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_subcategory', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_model', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->unsignedInteger('transaction_volume')->nullable();
            $table->unsignedInteger('transaction_value')->nullable();
            $table->string('promoter_pan', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('promoter_pan_name', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('date_of_birth', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_name', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_account_number', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_account_name', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_account_type', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_branch', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_branch_ifsc', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_beneficiary_address1', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_beneficiary_address2', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_beneficiary_address3', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_beneficiary_city', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_beneficiary_state', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_beneficiary_pin', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('website_about', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('website_contact', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('website_privacy', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('website_terms', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('website_refund', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('website_pricing', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('website_login', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_proof_url', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_operation_proof_url', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_pan_url', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('address_proof_url', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('promoter_proof_url', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('promoter_pan_url', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('promoter_address_url', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('form_12a_url', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('form_80g_url', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('transaction_report_email', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('role', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('department', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('comment', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('steps_finished', 255)->charset('utf8')->collation('utf8_bin')->default('[]');
            $table->unsignedInteger('activation_progress')->default(0);
            $table->tinyInteger('locked')->default(0);
            $table->string('activation_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('poi_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('poa_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_details_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('activation_flow', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('international_activation_flow', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->tinyInteger('live_transaction_done')->nullable();;
            $table->string('clarification_mode', 15)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->json('kyc_clarification_reasons')->nullable();
            $table->json('kyc_additional_details')->nullable();
            $table->char('kyc_id', 14)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->integer('archived_at')->nullable();
            $table->char('reviewer_id', 14)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('issue_fields')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('issue_fields_reason')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('internal_notes')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->text('custom_fields')->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('marketplace_activation_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('virtual_accounts_activation_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('subscriptions_activation_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->tinyInteger('submitted')->default(0);
            $table->integer('submitted_at')->nullable();
            $table->integer('created_at');
            $table->integer('updated_at');
            $table->smallInteger('estd_year')->nullable();
            $table->string('authorized_signatory_residential_address', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->date('authorized_signatory_dob')->nullable();
            $table->string('platform', 40)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->char('fund_account_validation_id', 14)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->date('date_of_establishment')->nullable();
            $table->integer('penny_testing_updated_at')->nullable();
            $table->string('company_pan_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('gstin_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('cin_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('personal_pan_doc_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('company_pan_doc_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('bank_details_doc_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('shop_establishment_number', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('shop_establishment_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->json('client_applications')->nullable();
            $table->string('onboarding_milestone', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_suggested_pin', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('business_suggested_address', 255)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('fraud_type', 100)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->char('bas_business_id', 14)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('msme_doc_verification_status', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->string('activation_form_milestone', 30)->charset('utf8')->collation('utf8_bin')->nullable();
            $table->json('fund_addition_va_ids')->nullable();
            $table->string('iec_code', 20)->collation('utf8_bin')->nullable();
            $table->char('audit_id', 14)->charset('utf8mb4')->collation('utf8mb4_bin')->nullable();
            $table->string('bank_branch_code_type', 255)->collation('utf8_bin')->nullable();
            $table->string('bank_branch_code', 255)->collation('utf8_bin')->nullable();
            $table->string('industry_category_code', 255)->collation('utf8_bin')->nullable();
            $table->string('industry_category_code_type', 255)->collation('utf8_bin')->nullable();

            $table->index('activation_status');
            $table->index('archived_at');
            $table->index('created_at');
            $table->index('updated_at');
            $table->index('bank_details_verification_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_details');
    }
};
