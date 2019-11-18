import { ndcFields } from 'merchant/components/Activation/ActivationFormMap';
import Input from 'common/new-ui/Input';

export const getNeedsClarificationTabsData = (allFieldsMap, needsKyc) => {
  const allFieldsHash = {};
  const kycFieldsMap = {};
  const kycTabContent = [];

  const addField = f => {
    allFieldsHash[f.name || f._name] = f;
  };
  const addToKYCTab = field => {
    if (!Boolean(kycFieldsMap[field])) {
      kycTabContent.push(allFieldsHash[field]);
      kycFieldsMap[field] = true;
    }
  };
  const scanFields = fields => {
    for (let f of fields) {
      if (Array.isArray(f)) {
        scanFields(f);
      } else {
        addField(f);
      }
    }
  };

  const prepareField = field => {
    let reasons = [];
    if (allFieldsHash[field]) {
      for (let r of needsKyc.clarification_reasons[field]) {
        if (r.reason_type === 'predefined') {
          reasons.push(
            predefinedReasons[field].reasons[r.reason_code].description
          );
        }
      }

      if (
        typeof allFieldsHash[field].linkedfields !== 'undefined' &&
        Array.isArray(allFieldsHash[field].linkedfields)
      ) {
        //Push all depending fields first
        allFieldsHash[field].linkedfields.forEach((dField, i) => {
          //Add all the reasons for this fields to first dependent field only
          if (i === 0) {
            allFieldsHash[dField].reasons = reasons;
          }
          addToKYCTab(dField);
        });
      } else {
        //Add reasons to main field if there are no dependent fields
        allFieldsHash[field].reasons = reasons;
      }
      addToKYCTab(field);
    }
  };

  const generateNewField = (key, fieldObj) => {
    const newField = {};
    const fieldTypes = {
      document: Input.File,
    };

    return newField;
  };
  scanFields(allFieldsMap);
  scanFields(ndcFields);

  for (let field in needsKyc.clarification_reasons) {
    prepareField(field);
  }

  for (let field in needsKyc.additional_details) {
    const generatedField = generateNewField(needsKyc.additional_details[field]);
    allFieldsHash[field] = generatedField;
    prepareField(field);
  }

  return kycTabContent;
};

const predefinedReasons = {
  contact_name: {
    reasons: {
      provide_poc: {
        description:
          'Please provide a provide a POC that we can reach out to in case of issues associated with your account.',
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
      is_company_reg: {
        description: 'Is your company a registered entity?',
      },
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
        description:
          'Your website/app is currently not live. When will your website go live?',
      },
    },
  },
  promoter_pan: {
    reasons: {
      update_director_pan: {
        description: 'Please update PAN details of a director listed by MCA',
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
      unable_to_validate_acc_number: {
        description:
          "We're unable to validate the account number from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.",
      },
    },
  },
  bank_account_name: {
    reasons: {
      unable_to_validate_beneficiary_name: {
        description:
          "We're unable to validate the beneficiary name from the document attached. Kindly submit a cancelled cheque/welcome letter merged along with the document.",
      },
    },
  },
  bank_branch_ifsc: {
    reasons: {
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
        description:
          'Please submit all the pages of the Partnership Deed merged as one document.',
      },
      submit_gstin_msme_shops_estab_certificate: {
        description:
          'Please submit the GSTIN/MSME/Shops and Establishment Certificate',
      },
      submit_complete_trust_deed: {
        description:
          'Please submit all the pages of the Trust Deed merged as one document',
      },
      submit_society_reg_certificate: {
        description: 'Please submit the Society registration certificate',
      },
      business_proof_outdated: {
        description:
          'The validity of the business proof attached has elapsed. Please submit the updated registration certificate',
      },
      illegible_doc: {
        description:
          'The document attached is not legible. Please resubmit a clearer copy',
      },
      submit_reg_business_pan_card: {
        description:
          'Please submit a copy of the PAN Card[in the name of registered business]',
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
    },
  },
};
