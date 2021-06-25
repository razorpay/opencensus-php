import { ndcFields } from 'merchant/components/Activation/ActivationFormMap';
import { LLPIN_BusinessTypes } from './ActivationFormMap';

export const getNeedsClarificationTabsData = (allFieldsMap, activationDetails) => {
  const allFieldsHash = {};
  const kycFieldsMap = {};
  const kycTabContent = [];
  const needsKyc = activationDetails.kyc_clarification_reasons;

  const hasLLPINActive =
    activationDetails.business_type &&
    LLPIN_BusinessTypes.indexOf(Number(activationDetails.business_type)) !== -1;

  const addField = (f) => {
    if (!hasLLPINActive && f.label === 'LLPIN') {
      return;
    }
    const name = (f.name || f._name);
    if(name) {
      allFieldsHash[name] = f;
    }
  };
  const addToKYCTab = (field) => {
    if (!kycFieldsMap[field]) {
      kycTabContent.push(allFieldsHash[field]);
      kycFieldsMap[field] = true;
    }
  };
  const scanFields = (fields) => {
    for (let f of fields) {
      if (Array.isArray(f)) {
        scanFields(f);
      } else {
        addField(f);
      }
    }
  };

  const prepareField = (field, clarificationDetails, forceMap) => {
    let reasons = [];
    const latestNc = needsKyc.nc_count; // latest needs clarrification

    const origKey = field;
    if (!allFieldsHash[field] || Boolean(forceMap)) {
      field = generateNewField(field, clarificationDetails[origKey], forceMap);
    }

    if (allFieldsHash[field]) {
      if (latestNc) {
        clarificationDetails[origKey].map((key) => {
          const { from, nc_count } = key;
          if (
            ((from === 'admin' || from === 'system') && nc_count === latestNc) ||
            (forceMap && !nc_count)
          ) {
            if (key.reason_type === 'predefined') {
              try {
                reasons.push(predefinedReasons[origKey].reasons[key.reason_code].description);
              } catch (error) {
                console.log(error);
              }
            } else if (key.reason_type === 'custom') {
              try {
                reasons.push(key.reason_code);
              } catch (error) {
                console.log(error);
              }
            }
          }
        });
      } else {
        // support for the additional_details
        for (let r of clarificationDetails[origKey]) {
          if (r.reason_type === 'predefined') {
            try {
              reasons.push(predefinedReasons[origKey].reasons[r.reason_code].description);
            } catch (error) {
              console.log(error);
            }
          } else if (r.reason_type === 'custom') {
            try {
              reasons.push(r.reason_code);
            } catch (error) {
              console.log(error);
            }
          }
        }
      }

      if (
        typeof allFieldsHash[field].linkedfields !== 'undefined' &&
        Array.isArray(allFieldsHash[field].linkedfields)
      ) {
        //Push all depending fields first
        allFieldsHash[field].linkedfields.forEach((dField, i) => {
          //Add all the reasons for this fields to first dependent field only
          if (i === 0 && reasons.length) {
            allFieldsHash[dField].reasons = reasons;
          }
          if (reasons.length > 0) {
            addToKYCTab(dField);
          }
        });
      } else {
        //Add reasons to main field if there are no dependent fields
        if (reasons.length > 0) {
          allFieldsHash[field].reasons = reasons;
        }
      }
      if (reasons.length > 0) {
        addToKYCTab(field);
      }
    }
  };

  const generateNewField = (key, fieldObj, forceMap) => {
    //	const newField = {};
    //There are two ways to generate a new field
    //1. The field already is a part fo all fields in the form
    //2. Field is altogether a new attribute, then it should be generated dynamically
    // LHS (KEYS) are server side attributes and reasons are read based on this key
    // RHS (VALUE) are key names of UI input component that would be rendered for getting user input
    const mappedFields = {
      cancelled_cheque: 'bank_proof_doc',
      bank_statement: 'bank_proof_doc',
      aadhar_front: 'address_proof_front',
      aadhar_back: 'address_proof_back',
      passport_front: 'address_proof_front',
      passport_back: 'address_proof_back',
      voter_id_front: 'address_proof_front',
      voter_id_back: 'address_proof_back',
      driver_license_front: 'address_proof_front',
      driver_license_back: 'address_proof_back',
      gst_certificate: 'business_proof_type_doc',
      msme_certificate: 'business_proof_type_doc',
      shop_establishment_certificate: 'business_proof_type_doc',
    };

    if (Boolean(mappedFields[key]) || (Boolean(forceMap) && allFieldsHash[mappedFields[key]])) {
      return mappedFields[key];
    }

    return key;
    //Implement functionality for custom fields here
    //Push the field to allFieldsHash & return the name of field
    //Return key if not in mappedFields
  };

  try {
    scanFields(allFieldsMap);
    scanFields(ndcFields);

    const bankDetailsforNC = {};
    let removedBankDetailsFromNC = needsKyc.clarification_reasons;
    if (needsKyc.clarification_reasons) {
      for (const [key, value] of Object.entries(needsKyc.clarification_reasons)) {
        if (
          key === 'bank_account_name' ||
          key === 'bank_branch_ifsc' ||
          key === 'bank_account_number' ||
          key === 'bank_proof'
        ) {
          bankDetailsforNC[key] = value;
          removedBankDetailsFromNC = Object.assign({}, removedBankDetailsFromNC);
          delete removedBankDetailsFromNC[key];
        }
      }
    }

    const newClarificationDetails = { ...removedBankDetailsFromNC, ...bankDetailsforNC };

    for (let field in newClarificationDetails) {
      prepareField(field, newClarificationDetails);
    }
    for (let field in needsKyc.additional_details) {
      // const newFieldName = generateNewField(field, needsKyc.additional_details[field]);
      prepareField(field, needsKyc.additional_details, true);
    }

    return kycTabContent;
  } catch (error) {
    console.log(error);
  }
};

const predefinedBankReasons = {
  bank_account_change_request_for_prop_ngo_trust: {
      "description": "Entered bank details are incorrect, please share company bank account details or authorised signatory details."
  },
  bank_account_change_request_for_unregistered: {
      "description": "Entered bank details are incorrect, please share signatory personal account details"
  },
  bank_account_change_request_for_pvt_public_llp: {
      "description": "Entered bank details are incorrect, please share company bank account details."
  }
};

const predefinedReasons = {
  contact_name: {
    reasons: {
      provide_poc: {
        description:
          'Please provide a POC that we can reach out to in case of issues associated with your account.',
      },
    },
  },
  contact_mobile: {
    reasons: {
      invalid_contact_number: {
        description: 'Please provide a valid contact number',
      },
    },
  },
  business_type: {
    reasons: {
      is_company_reg: { description: 'Is your company a registered entity?' },
    },
  },
  business_category: {
    reasons: {
      services_offered: {
        description: 'What are some of the services/products that are offered?',
      },
    },
  },
  business_subcategory: {
    reasons: {
      services_offered: {
        description: 'What are some of the services/products that are offered?',
      },
    },
  },
  business_website: {
    reasons: {
      website_not_live: {
        description: 'Your website/app is currently not live. When will your website go live?',
      },
    },
  },
  promoter_pan: {
    reasons: {
      update_director_pan: {
        description: 'Please update PAN details of a director listed by MCA',
      },
      update_proprietor_pan: {
        description: 'Please update PAN of the Propreitor',
      },
    },
  },
  promoter_pan_name: {
    reasons: {
      signatory_name_not_matched: {
        description:
          "Entered PAN Name doesn't match company incorporation records, please enter correct Authorised Signatory PAN Name",
      },
    },
  },
  business_name: {
    reasons: {
      company_name_not_matched: {
        description:
          "Entered Business Name doesn't match company incorporation records, please enter correct Business Name.",
      },
    },
  },
  company_pan_name: {
    reasons: {
      update_director_pan: {
        description: 'Please update PAN details of a director listed by MCA',
      },
    },
  },
  bank_account_number: {
    reasons: {
      ...predefinedBankReasons,
      unable_to_validate_acc_number: {
        description:
          "We're unable to validate the account number from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.",
      },
    },
  },
  bank_account_name: {
    reasons: {
      ...predefinedBankReasons,
      unable_to_validate_beneficiary_name: {
        description:
          "We're unable to validate the beneficiary name from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.",
      },
    },
  },
  bank_branch_ifsc: {
    reasons: {
      ...predefinedBankReasons,
      unable_to_validate_ifsc: {
        description:
          "We're unable to validate the IFSC from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.",
      },
    },
  },
  business_proof_url: {
    reasons: {
      submit_incorporation_certificate: {
        description: 'Please submit the Certificate of Incorporation',
      },
      submit_complete_partnership_deed: {
        description: 'Please submit all the pages of the Partnership Deed merged as one document.',
      },
      submit_gstin_msme_shops_estab_certificate: {
        description: 'Please submit the GSTIN/MSME/Shops and Establishment Certificate',
      },
      submit_complete_trust_deed: {
        description: 'Please submit all the pages of the Trust Deed merged as one document',
      },
      submit_society_reg_certificate: {
        description: 'Please submit the Society registration certificate',
      },
      business_proof_outdated: {
        description:
          'The validity of the business proof attached has elapsed. Please submit the updated registration certificate',
      },
      illegible_doc: {
        description: 'The document attached is not legible. Please resubmit a clearer copy',
      },
      submit_reg_business_pan_card: {
        description: 'Please submit a copy of the PAN Card[in the name of registered business]',
      },
    },
  },
  address_proof_url: {
    reasons: {
      unable_to_validate_acc_number: {
        description:
          "We're unable to validate the account number from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.",
      },
      unable_to_validate_beneficiary_name: {
        description:
          "We're unable to validate the beneficiary name from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.",
      },
      unable_to_validate_ifsc: {
        description:
          "We're unable to validate the IFSC from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.",
      },
      resubmit_cancelled_cheque: {
        description:
          'The statements or cancelled cheque attached is not legible. Please resubmit a clear copy.',
      },
    },
  },
  promoter_address_url: {
    reasons: {
      submit_complete_director_address_proof: {
        description:
          'Please submit address proof[both photo ID and address page merged as one document] of a director listed on the MCA website whose PAN details have been submitted under the Tab- Registration Details',
      },
      submit_complete_aadhaar: {
        description:
          'Please submit both photo ID and address page of the Aadhaar Card- merged as one document ',
      },
      submit_complete_passport: {
        description:
          'Please submit both photo ID and address page of the Passport- merged as one document ',
      },
      submit_complete_election_card: {
        description:
          'Please submit both photo ID and address page of the Election Card- merged as one document ',
      },
      address_proof_outdated: {
        description:
          'The validity of the address proof attached has elapsed. Please submit the updated document',
      },
      submit_driving_license: {
        description:
          'Please submit both photo ID and address page of the driving license - merged as one document.',
      },
    },
  },
  aadhar_back: {
    reasons: {
      illegible_doc: {
        description: 'The document attached is not legible. Please resubmit a clearer copy',
      },
    },
  },
  aadhar_front: {
    reasons: {
      illegible_doc: {
        description: 'The document attached is not legible. Please resubmit a clearer copy',
      },
    },
  },
  voter_id_front: {
    reasons: {
      illegible_doc: {
        description: 'The document attached is not legible. Please resubmit a clearer copy',
      },
    },
  },
  voter_id_back: {
    reasons: {
      illegible_doc: {
        description: 'The document attached is not legible. Please resubmit a clearer copy',
      },
    },
  },
  driver_license_front: {
    reasons: {
      illegible_doc: {
        description: 'The document attached is not legible. Please resubmit a clearer copy',
      },
    },
  },
  driver_license_back: {
    reasons: {
      illegible_doc: {
        description: 'The document attached is not legible. Please resubmit a clearer copy',
      },
    },
  },
  passport_front: {
    reasons: {
      illegible_doc: {
        description: 'The document attached is not legible. Please resubmit a clearer copy',
      },
    },
  },
  passport_back: {
    reasons: {
      illegible_doc: {
        description: 'The document attached is not legible. Please resubmit a clearer copy',
      },
    },
  },
  cancelled_cheque: {
    reasons: {
      illegible_doc: {
        description: 'The document attached is not legible. Please resubmit a clearer copy',
      },
      unable_to_validate_acc_number: {
        description:
          "We're unable to validate the account details from the information provided by you. Kindly submit a scanned copy of cancelled cheque.",
      },
      unable_to_validate_beneficiary_name: {
        description:
          "We're unable to validate the account details from the information provided by you. Kindly submit a scanned copy of cancelled cheque.",
      },
      unable_to_validate_ifsc: {
        description:
          "We're unable to validate the account details from the information provided by you. Kindly submit a scanned copy of cancelled cheque.",
      },
    },
  },
  business_pan_url: {
    reasons: {
      submit_company_pan: {
        description: 'Please submit a copy of the Company PAN Card',
      },
      submit_proprietor_pan: {
        description: 'Please submit a copy of the Proprietor PAN Card',
      },
    },
  },
  company_cin: {
    reasons: {
      invalid_cin_number: {
        description: 'The CIN number you have entered is invalid, please enter valid details.',
      },
      cin_data_unavailable: {
        description: "We weren't able to validate your CIN number, please check and edit the same.",
      },
      invalid_llpin_number: {
        description: 'The LLPIN number you have entered is invalid, please enter valid details.',
      },
      llpin_data_unavailable: {
        description:
          "We weren't able to validate your LLPIN number, please check and edit the same.",
      },
    },
  },
  gstin: {
    reasons: {
      gstin_data_unavailable: {
        description:
          "We weren't able to validate your GSTIN number, please check and edit the same.",
      },
      invalid_gstin_number: {
        description: 'The GSTIN number you have entered is invalid, please enter valid details.',
      },
    },
  },
  shop_establishment_number: {
    reasons: {
      invalid_shop_establishment_number: {
        description:
          'The SHOP ESTABLISHMENT NUMBER you have entered is invalid, please enter valid details.',
      },
      shop_establishment_data_unavailable: {
        description:
          " We weren't able to validate your SHOP ESTABLISHMENT number, please check and edit the same.",
      },
    },
  },
};
