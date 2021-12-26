/* eslint-disable */
import { ndcFields, LLPIN_BusinessTypes } from 'merchant/components/Activation/ActivationFormMap';

export const getNeedsClarificationTabsData = (
  allFieldsMap,
  activationDetails,
  clarificationReasons,
  isActivationFormFullView = false,
) => {
  const allFieldsHash = {};
  const kycFieldsMap = {};
  const kycTabContent = [];
  const needsKyc = activationDetails.kyc_clarification_reasons;

  const hasLLPINActive =
    activationDetails.business_type &&
    LLPIN_BusinessTypes.indexOf(Number(activationDetails.business_type)) !== -1;

  const addField = (f) => {
    if (!f) {
      return;
    }
    if (!hasLLPINActive && f.label === 'LLPIN') {
      return;
    }
    const name = f.name || f._name;
    if (name) {
      allFieldsHash[name] = f;
    }
  };

  const groupFields = (relatedFields) => {
    relatedFields.forEach((field) => {
      if (field.field_name === 'cancelled_cheque' || field.field_name === 'bank_statement') {
        // bank_proof and bank_proof_doc won't come from BE and its defined on FE only
        kycTabContent.push(allFieldsHash.bank_proof, allFieldsHash.bank_proof_doc);
      } else {
        kycTabContent.push(allFieldsHash[field.field_name]);
      }
      kycFieldsMap[field.field_name] = true;
    });
  };

  const addToKYCTab = (field, fieldValue) => {
    if (!kycFieldsMap[field]) {
      kycTabContent.push(allFieldsHash[field]);
      kycFieldsMap[field] = true;
      if (fieldValue?.related_fields?.length) {
        groupFields(fieldValue.related_fields);
      }
    }
  };
  const scanFields = (fields) => {
    for (const f of fields) {
      if (Array.isArray(f)) {
        scanFields(f);
      } else {
        addField(f);
      }
    }
  };

  const prepareField = (field, clarificationDetails, forceMap) => {
    const reasons = [];
    const latestNc = needsKyc.nc_count; // latest needs clarrification
    let latestClarificationField = {};

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
                if (
                  clarificationReasons[origKey] &&
                  clarificationReasons[origKey].reasons[key.reason_code]
                ) {
                  reasons.push(clarificationReasons[origKey].reasons[key.reason_code].description);
                }
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
            latestClarificationField = { ...latestClarificationField, ...key };
          }
        });
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
      } else if (reasons.length > 0) {
        //Add reasons to main field if there are no dependent fields
        allFieldsHash[field].reasons = reasons;
      }
      if (reasons.length > 0) {
        addToKYCTab(field, latestClarificationField);
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
    let mappedFields = {
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
    //if isActivationFormFullView is true, add business_website: 'payment_channels' for mapping new flow
    if (isActivationFormFullView) {
      mappedFields = { ...mappedFields, business_website: 'payment_channels' };
    }

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
    let removedBankDetailsFromNC = needsKyc.clarification_reasons_v2;
    if (needsKyc.clarification_reasons_v2) {
      for (const [key, value] of Object.entries(needsKyc.clarification_reasons_v2)) {
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

    for (const field in newClarificationDetails) {
      prepareField(field, newClarificationDetails, true);
    }

    return kycTabContent;
  } catch (error) {
    console.log(error);
  }
};
