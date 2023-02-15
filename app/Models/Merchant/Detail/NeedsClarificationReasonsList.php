<?php

namespace RZP\Models\Merchant\Detail;

class NeedsClarificationReasonsList
{
    const PROVIDE_POC                                       = 'provide_poc';
    const INVALID_CONTACT_NUMBER                            = 'invalid_contact_number';
    const IS_COMPANY_REG                                    = 'is_company_reg';
    const SERVICES_OFFERED                                  = 'services_offered';
    const WEBSITE_NOT_LIVE                                  = 'website_not_live';
    const UPDATE_DIRECTOR_PAN                               = 'update_director_pan';
    const UNABLE_TO_VALIDATE_ACC_NUMBER                     = 'unable_to_validate_acc_number';
    const BANK_ACCOUNT_CHANGE_REQUEST_FOR_PROP_NGO_TRUST    = 'bank_account_change_request_for_prop_ngo_trust';
    const BANK_ACCOUNT_CHANGE_REQUEST_FOR_UNREGISTERED      = 'bank_account_change_request_for_unregistered';
    const BANK_ACCOUNT_CHANGE_REQUEST_FOR_PVT_PUBLIC_LLP    = 'bank_account_change_request_for_pvt_public_llp';
    const UNABLE_TO_VALIDATE_BENEFICIARY_NAME               = 'unable_to_validate_beneficiary_name';
    const UNABLE_TO_VALIDATE_IFSC                           = 'unable_to_validate_ifsc';
    const SUBMIT_INCORPORATION_CERTIFICATE                  = 'submit_incorporation_certificate';
    const SUBMIT_COMPLETE_PARTNERSHIP_DEED                  = 'submit_complete_partnership_deed';
    const SUBMIT_GSTIN_MSME_SHOPS_ESTAB_CERTIFICATE         = 'submit_gstin_msme_shops_estab_certificate';
    const SUBMIT_COMPLETE_TRUST_DEED                        = 'submit_complete_trust_deed';
    const SUBMIT_SOCIETY_REG_CERTIFICATE                    = 'submit_society_reg_certificate';
    const BUSINESS_PROOF_OUTDATED                           = 'business_proof_outdated';
    const ILLEGIBLE_DOC                                     = 'illegible_doc';
    const SUBMIT_REG_BUSINESS_PAN_CARD                      = 'submit_reg_business_pan_card';
    const SUBMIT_COMPLETE_DIRECTOR_ADDRESS_PROOF            = 'submit_complete_director_address_proof';
    const SUBMIT_COMPLETE_AADHAAR                           = 'submit_complete_aadhaar';
    const SUBMIT_COMPLETE_PASSPORT                          = 'submit_complete_passport';
    const SUBMIT_COMPLETE_ELECTION_CARD                     = 'submit_complete_election_card';
    const ADDRESS_PROOF_OUTDATED                            = 'address_proof_outdated';
    const UPDATE_PROPRIETOR_PAN                             = 'update_proprietor_pan';
    const SUBMIT_COMPANY_PAN                                = 'submit_company_pan';
    const SUBMIT_PROPRIETOR_PAN                             = 'submit_proprietor_pan';
    const RESUBMIT_CANCELLED_CHEQUE                         = 'resubmit_cancelled_cheque';
    const SUBMIT_DRIVING_LICENSE                            = 'submit_driving_license';

    const INVALID_GSTIN_NUMBER                              = 'invalid_gstin_number';
    const INVALID_CIN_NUMBER                                = 'invalid_cin_number';
    const INVALID_LLPIN_NUMBER                              = 'invalid_llpin_number';
    const INVALID_SHOP_ESTABLISHMENT_NUMBER                 = 'invalid_shop_establishment_number';
    const INVALID_PERSONAL_PAN_NUMBER                       = 'invalid_personal_pan_number';
    const INVALID_COMPANY_PAN_NUMBER                        = 'invalid_company_pan_number';

    const SHOP_ESTABLISHMENT_DATA_UNAVAILABLE               = 'shop_establishment_data_unavailable';
    const GSTIN_DATA_UNAVAILABLE                            = 'gstin_data_unavailable';
    const CIN_DATA_UNAVAILABLE                              = 'cin_data_unavailable';
    const LLPIN_DATA_UNAVAILABLE                            = 'llpin_data_unavailable';
    const NO_DOC_LIMIT_BREACH                               = 'no_doc_limit_breach';
    const GMV_LIMIT_BREACHED_FOR_NO_DOC_ONBOARDING          = 'Your GMV limit has been breached, kindly share additional details to get your account reactivated.';

    const NO_DOC_RETRY_EXHAUSTED                            = 'no_doc_retry_exhausted';
    const NO_DOC_RETRY_EXHAUSTED_MESSAGE                    = 'You have exhausted all your retry attempts, kindly share additional details to get your account reactivated.';

    const NO_DOC_KYC_FAILURE                               = 'no_doc_kyc_failure';
    const NO_DOC_KYC_FAILURE_MESSAGE                       = 'Your KYC details have failed internal checks. Please submit all the additional required details to get your account activated.';

    //Not Used please use these fields if required in future.
    const SHOP_ESTABLISHMENT_DATA_NOT_MATCHED               = 'shop_establishment_data_not_matched';
    const GSTIN_DATA_NOT_MATCHED                            = 'gstin_not_matched';
    const CIN_DATA_NOT_MATCHED                              = 'cin_data_not_matched';
    const LLPIN_DATA_NOT_MATCHED                            = 'llpin_data_not_matched';
    const PERSONAL_PAN_DATA_NOT_MATCHED                     = 'personal_pan_data_not_matched';
    const COMPANY_PAN_DATA_NOT_MATCHED                      = 'company_pan_data_not_matched';
    const GSTIN_NUMBER_NOT_MATCHED                          = 'gstin_number_not_matched';


    //spam detected error
    const PERSONAL_PAN_SPAM_DETECTED                        = 'personal_pan_spam_detected';
    const COMPANY_PAN_SPAM_DETECTED                         = 'company_pan_spam_detected';
    const GSTIN_SPAM_DETECTED                               = 'gstin_spam_detected';
    const BANK_ACCOUNT_SPAM_DETECTED                        = 'bank_account_spam_detected';

    //SignatoryName & CompanyName Not Matched.
    const SIGNATORY_NAME_NOT_MATCHED                = 'signatory_name_not_matched';
    const COMPANY_NAME_NOT_MATCHED                  = 'company_name_not_matched';

    const FIELD_ALREADY_EXIST = 'merchant_already_exist_with_same_field_value';

    //board resolution documents clarification reasons
    const AUTHORIZED_SIGNATORY_MISMATCH                           = 'authorized_signatory_mismatch';
    const PROVIDE_AUTHORIZED_SIGNATORY_SIGNED_AND_SEALED_DOCUMENT = 'provide_authorized_signatory_signed_and_sealed_document';

    const REASON_DETAILS = [
        self::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PROP_NGO_TRUST                                      => [
            NeedsClarificationMetaData::DESCRIPTION => 'Entered bank details are incorrect, please share company bank account details or authorised signatory details.',],
        self::BANK_ACCOUNT_CHANGE_REQUEST_FOR_UNREGISTERED                                      => [
            NeedsClarificationMetaData::DESCRIPTION => 'Entered bank details are incorrect, please share signatory personal account details',],
        self::BANK_ACCOUNT_CHANGE_REQUEST_FOR_PVT_PUBLIC_LLP                                      => [
            NeedsClarificationMetaData::DESCRIPTION => 'Entered bank details are incorrect, please share company bank account details.',],
        self::INVALID_CIN_NUMBER                                      => [
            NeedsClarificationMetaData::DESCRIPTION => 'The CIN number you have entered is invalid, please enter valid details.',],
        self::INVALID_SHOP_ESTABLISHMENT_NUMBER                       => [
            NeedsClarificationMetaData::DESCRIPTION => 'The SHOP ESTABLISHMENT NUMBER you have entered is invalid, please enter valid details.',],
        self::INVALID_PERSONAL_PAN_NUMBER                             => [
            NeedsClarificationMetaData::DESCRIPTION => "The Personal PAN number you have entered is invalid as per Government's Database, please enter valid details.",],
        self::INVALID_COMPANY_PAN_NUMBER                              => [
            NeedsClarificationMetaData::DESCRIPTION => "The Business PAN number you have entered is invalid as per Government's Database, please enter valid details.",],
        self::INVALID_GSTIN_NUMBER                                    => [
            NeedsClarificationMetaData::DESCRIPTION => 'The GSTIN number you have entered is invalid, please enter valid details.',],
        self::INVALID_LLPIN_NUMBER                                    => [
            NeedsClarificationMetaData::DESCRIPTION => 'The LLPIN number you have entered is invalid, please enter valid details.',],
        self::GSTIN_DATA_UNAVAILABLE                                  => [
            NeedsClarificationMetaData::DESCRIPTION => 'We weren\'t able to validate your GSTIN number, please check and edit the same.',],
        self::CIN_DATA_UNAVAILABLE                                    => [
            NeedsClarificationMetaData::DESCRIPTION => 'We weren\'t able to validate your CIN number, please check and edit the same.'],
        self::SHOP_ESTABLISHMENT_DATA_UNAVAILABLE                     => [
            NeedsClarificationMetaData::DESCRIPTION => 'We weren\'t able to validate your SHOP ESTABLISHMENT number, please check and edit the same.'],
        self::LLPIN_DATA_UNAVAILABLE                                  => [
            NeedsClarificationMetaData::DESCRIPTION => 'We weren\'t able to validate your LLPIN number, please check and edit the same.'],
        self::UPDATE_PROPRIETOR_PAN                                   => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please update PAN of the Proprietor.',],
        self::SUBMIT_COMPANY_PAN                                      => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit a copy of the Company PAN Card',],
        self::SUBMIT_PROPRIETOR_PAN                                   => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit a copy of the Proprietor PAN Card.',],
        self::RESUBMIT_CANCELLED_CHEQUE                               => [
            NeedsClarificationMetaData::DESCRIPTION => 'We\'re unable to validate your bank account details. Kindly submit a copy of cancelled cheque by logging into your Razorpay dashboard.',],
        self::SUBMIT_DRIVING_LICENSE                                  => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit both photo ID and address page of the driving license- merged as one document.',],
        self::PROVIDE_POC                                             => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please provide a POC that we can reach out to in case of issues associated with your account.',],
        self::INVALID_CONTACT_NUMBER                                  => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please provide a valid contact number',],
        self::IS_COMPANY_REG                                          => [
            NeedsClarificationMetaData::DESCRIPTION => 'Is your company a registered entity?'],
        self::SERVICES_OFFERED                                        => [
            NeedsClarificationMetaData::DESCRIPTION => 'What are some of the services/products that are offered?',],
        self::WEBSITE_NOT_LIVE                                        => [
            NeedsClarificationMetaData::DESCRIPTION => 'Your website/app is currently not live. When will your website go live?',],
        self::UPDATE_DIRECTOR_PAN                                     => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please update PAN details of a director listed by MCA',],
        self::UNABLE_TO_VALIDATE_ACC_NUMBER                           => [
            NeedsClarificationMetaData::DESCRIPTION => 'We\'re unable to validate the account number from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.',],
        self::UNABLE_TO_VALIDATE_BENEFICIARY_NAME                     => [
            NeedsClarificationMetaData::DESCRIPTION => 'We\'re unable to validate the beneficiary name from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.',],
        self::UNABLE_TO_VALIDATE_IFSC                                 => [
            NeedsClarificationMetaData::DESCRIPTION => 'We\'re unable to validate the IFSC from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.',],
        self::SUBMIT_INCORPORATION_CERTIFICATE                        => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit the Certificate of Incorporation',],
        self::SUBMIT_COMPLETE_PARTNERSHIP_DEED                        => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit all the pages of the Partnership Deed merged as one document.',],
        self::SUBMIT_GSTIN_MSME_SHOPS_ESTAB_CERTIFICATE               => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit the GSTIN/MSME/Shops and Establishment Certificate',],
        self::SUBMIT_COMPLETE_TRUST_DEED                              => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit all the pages of the Trust Deed merged as one document',],
        self::SUBMIT_SOCIETY_REG_CERTIFICATE                          => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit the Society registration certificate',],
        self::BUSINESS_PROOF_OUTDATED                                 => [
            NeedsClarificationMetaData::DESCRIPTION => 'The validity of the business proof attached has elapsed. Please submit the updated registration certificate',],
        self::ILLEGIBLE_DOC                                           => [
            NeedsClarificationMetaData::DESCRIPTION => 'The document attached is not legible. Please resubmit a clearer copy',],
        self::SUBMIT_REG_BUSINESS_PAN_CARD                            => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit a copy of the PAN Card[in the name of registered business]',],
        self::SUBMIT_COMPLETE_DIRECTOR_ADDRESS_PROOF                  => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit address proof[both photo ID and address page merged as one document] of a director listed on the MCA website whose PAN details have been submitted under the Tab- Registration Details',],
        self::SUBMIT_COMPLETE_AADHAAR                                 => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit both photo ID and address page of the Aadhaar Card- merged as one document ',],
        self::SUBMIT_COMPLETE_PASSPORT                                => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit both photo ID and address page of the Passport- merged as one document ',],
        self::SUBMIT_COMPLETE_ELECTION_CARD                           => [
            NeedsClarificationMetaData::DESCRIPTION => 'Please submit both photo ID and address page of the Election Card- merged as one document ',],
        self::ADDRESS_PROOF_OUTDATED                                  => [
            NeedsClarificationMetaData::DESCRIPTION => 'The validity of the address proof attached has elapsed. Please submit the updated document',],
        self::AUTHORIZED_SIGNATORY_MISMATCH                           => [
            NeedsClarificationMetaData::DESCRIPTION => 'The PAN & Address Proof submitted is not of the authorized signatory as per the Board Resolution Authorisation',],
        self::PROVIDE_AUTHORIZED_SIGNATORY_SIGNED_AND_SEALED_DOCUMENT => [
            NeedsClarificationMetaData::DESCRIPTION => 'The PAN & Address Proof submitted is not of the authorized signatory as per the Board Resolution Authorisation',],
        self::GSTIN_DATA_NOT_MATCHED                                  => [
            NeedsClarificationMetaData::DESCRIPTION => 'Gstin Signatory Name not matched with please submit correct details.',],
        self::CIN_DATA_NOT_MATCHED                                    => [
            NeedsClarificationMetaData::DESCRIPTION => 'CIN Signatory Name not matched with please submit correct details.',],
        self::LLPIN_DATA_NOT_MATCHED                                  => [
            NeedsClarificationMetaData::DESCRIPTION => 'LLPIN Signatory Name not matched with please submit correct details.',],
        self::SHOP_ESTABLISHMENT_DATA_NOT_MATCHED                     => [
            NeedsClarificationMetaData::DESCRIPTION => 'Shops Establishment Signatory Name not matched with please submit correct details.',],
        self::SIGNATORY_NAME_NOT_MATCHED                              => [
            NeedsClarificationMetaData::DESCRIPTION => 'Entered PAN Name doesn\'t match company incorporation records, please enter correct Authorised Signatory PAN Name.',],
        self::COMPANY_NAME_NOT_MATCHED                                => [
            NeedsClarificationMetaData::DESCRIPTION => 'Entered Business Name doesn\'t match company incorporation records, please enter correct Business Name.',],
        self::PERSONAL_PAN_DATA_NOT_MATCHED                           => [
            NeedsClarificationMetaData::DESCRIPTION => 'Entered Personal Pan Number doesn\'t match company incorporation records, please enter correct Personal Pan Number.'
        ],
        self::COMPANY_PAN_DATA_NOT_MATCHED                           => [
            NeedsClarificationMetaData::DESCRIPTION => 'Entered Business Pan Number doesn\'t match company incorporation records, please enter correct Business Pan Number.'
        ],
        self::GSTIN_NUMBER_NOT_MATCHED                                  => [
            NeedsClarificationMetaData::DESCRIPTION => 'GSTIN Number doesn\'t match company incorporation records, please enter correct GSTIN Number.',],
        self::PERSONAL_PAN_SPAM_DETECTED                             => [
            NeedsClarificationMetaData::DESCRIPTION => 'Max retry exceeded for Personal Pan Number.'
        ],
        self::COMPANY_PAN_SPAM_DETECTED                             => [
            NeedsClarificationMetaData::DESCRIPTION => 'Max retry exceeded for Business Pan Number.'
        ],
        self::GSTIN_SPAM_DETECTED                             => [
            NeedsClarificationMetaData::DESCRIPTION => 'Max retry exceeded for GSTIN Number.'
        ],
        self::BANK_ACCOUNT_SPAM_DETECTED            =>  [
            NeedsClarificationMetaData::DESCRIPTION => 'Max retry exceeded for bank account details.'
        ],
        self::NO_DOC_LIMIT_BREACH                                     => [
            NeedsClarificationMetaData::DESCRIPTION => 'Your GMV limit has been breached, kindly share additional details to get your account reactivated.',],
        self::FIELD_ALREADY_EXIST                                     => [
            NeedsClarificationMetaData::DESCRIPTION => 'Field value already exist',],
        self::NO_DOC_RETRY_EXHAUSTED => [
            NeedsClarificationMetaData::DESCRIPTION => 'You have exhausted all your retry attempts, kindly share additional details to get your account reactivated.',],
        self::NO_DOC_KYC_FAILURE => [
            NeedsClarificationMetaData::DESCRIPTION => 'Your KYC details have failed internal checks. Please submit all the additional required details to get your account activated.',],
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
