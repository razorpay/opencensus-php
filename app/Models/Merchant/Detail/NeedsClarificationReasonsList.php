<?php

namespace RZP\Models\Merchant\Detail;

class NeedsClarificationReasonsList
{
    const PROVIDE_POC                               = 'provide_poc';
    const INVALID_CONTACT_NUMBER                    = 'invalid_contact_number';
    const IS_COMPANY_REG                            = 'is_company_reg';
    const SERVICES_OFFERED                          = 'services_offered';
    const WEBSITE_NOT_LIVE                          = 'website_not_live';
    const UPDATE_DIRECTOR_PAN                       = 'update_director_pan';
    const UNABLE_TO_VALIDATE_ACC_NUMBER             = 'unable_to_validate_acc_number';
    const UNABLE_TO_VALIDATE_BENEFICIARY_NAME       = 'unable_to_validate_beneficiary_name';
    const UNABLE_TO_VALIDATE_IFSC                   = 'unable_to_validate_ifsc';
    const SUBMIT_INCORPORATION_CERTIFICATE          = 'submit_incorporation_certificate';
    const SUBMIT_COMPLETE_PARTNERSHIP_DEED          = 'submit_complete_partnership_deed';
    const SUBMIT_GSTIN_MSME_SHOPS_ESTAB_CERTIFICATE = 'submit_gstin_msme_shops_estab_certificate';
    const SUBMIT_COMPLETE_TRUST_DEED                = 'submit_complete_trust_deed';
    const SUBMIT_SOCIETY_REG_CERTIFICATE            = 'submit_society_reg_certificate';
    const BUSINESS_PROOF_OUTDATED                   = 'business_proof_outdated';
    const ILLEGIBLE_DOC                             = 'illegible_doc';
    const SUBMIT_REG_BUSINESS_PAN_CARD              = 'submit_reg_business_pan_card';
    const SUBMIT_COMPLETE_DIRECTOR_ADDRESS_PROOF    = 'submit_complete_director_address_proof';
    const SUBMIT_COMPLETE_AADHAAR                   = 'submit_complete_aadhaar';
    const SUBMIT_COMPLETE_PASSPORT                  = 'submit_complete_passport';
    const SUBMIT_COMPLETE_ELECTION_CARD             = 'submit_complete_election_card';
    const ADDRESS_PROOF_OUTDATED                    = 'address_proof_outdated';


    const REASON_DETAILS = [
        self::PROVIDE_POC                               => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please provide a POC that we can reach out to in case of issues associated with your account.',],
        self::INVALID_CONTACT_NUMBER                    => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please provide a valid contact number',],
        self::IS_COMPANY_REG                            => [
            NeedsClarificationMetaData::DESCRIPTION => 'Is your company a registered entity?'],
        self::SERVICES_OFFERED                          => [
            NeedsClarificationMetaData::DESCRIPTION => 'What are some of the services/products that are offered?',],
        self::WEBSITE_NOT_LIVE                          => [
            NeedsClarificationMetaData::DESCRIPTION => 'Your website/app is currently not live. When will your website go live?',],
        self::UPDATE_DIRECTOR_PAN                       => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please update PAN details of a director listed by MCA',],
        self::UNABLE_TO_VALIDATE_ACC_NUMBER             => [
            NeedsClarificationMetaData::DESCRIPTION => 'We\'re unable to validate the account number from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.',],
        self::UNABLE_TO_VALIDATE_BENEFICIARY_NAME       => [
            NeedsClarificationMetaData::DESCRIPTION => 'We\'re unable to validate the beneficiary name from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.',],
        self::UNABLE_TO_VALIDATE_IFSC                   => [
            NeedsClarificationMetaData::DESCRIPTION => 'We\'re unable to validate the IFSC from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.',],
        self::SUBMIT_INCORPORATION_CERTIFICATE          => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit the Certificate of Incorporation',],
        self::SUBMIT_COMPLETE_PARTNERSHIP_DEED          => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit all the pages of the Partnership Deed merged as one document.',],
        self::SUBMIT_GSTIN_MSME_SHOPS_ESTAB_CERTIFICATE => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit the GSTIN/MSME/Shops and Establishment Certificate',],
        self::SUBMIT_COMPLETE_TRUST_DEED                => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit all the pages of the Trust Deed merged as one document',],
        self::SUBMIT_SOCIETY_REG_CERTIFICATE            => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit the Society registration certificate',],
        self::BUSINESS_PROOF_OUTDATED                   => [
            NeedsClarificationMetaData::DESCRIPTION => 'The validity of the business proof attached has elapsed. Please submit the updated registration certificate',],
        self::ILLEGIBLE_DOC                             => [
            NeedsClarificationMetaData::DESCRIPTION => 'The document attached is not legible. Please resubmit a clearer copy',],
        self::SUBMIT_REG_BUSINESS_PAN_CARD              => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit a copy of the PAN Card[in the name of registered business]',],
        self::SUBMIT_COMPLETE_DIRECTOR_ADDRESS_PROOF    => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit address proof[both photo ID and address page merged as one document] of a director listed on the MCA website whose PAN details have been submitted under the Tab- Registration Details',],
        self::SUBMIT_COMPLETE_AADHAAR                   => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit both photo ID and address page of the Aadhaar Card- merged as one document ',],
        self::SUBMIT_COMPLETE_PASSPORT                  => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit both photo ID and address page of the Passport- merged as one document ',],
        self::SUBMIT_COMPLETE_ELECTION_CARD             => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit both photo ID and address page of the Election Card- merged as one document ',],
        self::ADDRESS_PROOF_OUTDATED                    => [
            NeedsClarificationMetaData::DESCRIPTION => 'The validity of the address proof attached has elapsed. Please submit the updated document',],
    ];


    /**
     * This function checks if the given reason detail is is valid
     *
     * @param string $reason
     *
     * @return boolean true/false
     */
    public static function isValidPredefinedReason(string $reason): bool
    {
        // return false if $reason is `reason_details`
        if (strtolower($reason) === 'reason_details')
        {
            return false;
        }

        $key = __CLASS__ . '::' . strtoupper($reason);

        return ((defined($key) === true) and (constant($key) === $reason));
    }
}
