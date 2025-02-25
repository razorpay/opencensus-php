/* eslint-disable */
import { ndcFields } from 'merchant/components/Activation/ActivationFormMap';
import React from "react";

export const getNeedsClarificationTabsData = (
  allFieldsMap,
  activationDetails,
  clarificationReasons,
) => {
  const allFieldsHash = {};
  const kycFieldsMap = {};
  const kycTabContent = [];
  const needsKyc = activationDetails?.kyc_clarification_reasons;

  const addField = (f) => {
    const name = f.name || f._name;
    if (name) {
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
    for (const f of fields) {
      if (Array.isArray(f)) {
        scanFields(f);
      } else {
        addField(f);
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
      gst_certificate: 'business_proof_type_doc',
    };

    if (Boolean(mappedFields[key]) || (Boolean(forceMap) && allFieldsHash[mappedFields[key]])) {
      return mappedFields[key];
    }

    return key;
    //Implement functionality for custom fields here
    //Push the field to allFieldsHash & return the name of field
    //Return key if not in mappedFields
  };

  const prepareField = (field, clarificationDetails, forceMap) => {
    const reasons = [];
    const latestNc = needsKyc?.nc_count; // latest needs clarrification

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
          }
        });
      } else {
        // support for the additional_details
        for (const r of clarificationDetails[origKey]) {
          if (r.reason_type === 'predefined') {
            try {
              if (
                clarificationReasons[origKey] &&
                clarificationReasons[origKey].reasons[key.reason_code]
              ) {
                reasons.push(clarificationReasons[origKey].reasons[r.reason_code].description);
              }
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

  try {
    scanFields(allFieldsMap);
    scanFields(ndcFields);

    const bankDetailsforNC = {};
    let removedBankDetailsFromNC = needsKyc?.clarification_reasons;
    if (needsKyc?.clarification_reasons) {
      for (const [key, value] of Object.entries(needsKyc.clarification_reasons)) {
        if (
          key === 'bank_account_name' ||
          key === 'bank_branch_ifsc' ||
          key === 'bank_account_number' ||
          key === 'bank_proof'
        ) {
          bankDetailsforNC[key] = value;
          removedBankDetailsFromNC = { ...removedBankDetailsFromNC };
          delete removedBankDetailsFromNC[key];
        }
      }
    }

    const newClarificationDetails = { ...removedBankDetailsFromNC, ...bankDetailsforNC };

    for (const field in newClarificationDetails) {
      prepareField(field, newClarificationDetails);
    }
    for (const field in needsKyc?.additional_details) {
      // const newFieldName = generateNewField(field, needsKyc.additional_details[field]);
      prepareField(field, needsKyc?.additional_details, true);
    }

    return kycTabContent;
  } catch (error) {
    console.log(error);
  }
};
