import Entity from './Entity';
import { merchantFetch } from 'merchant/utils/ajax';
import { uniqueArray } from 'common/utils/rzp-utils';

import { normalizeBoolean, isBlank, arrayDiff, autoPrefixUrls, trim } from 'common/utils/rzp-utils';

// Used for Activation
const activationStepMap = {
  1: [
    'contact_name',
    'contact_email',
    'transaction_report_email',
    'contact_mobile',
    'role',
    'department',
  ],
  2: [
    'business_type',
    'business_name',
    'business_dba',
    'business_international',
    'business_website',
    'business_paymentdetails',
    'business_model',
    'business_registered_address',
    'business_registered_state',
    'business_registered_city',
    'business_registered_pin',
    'business_operation_address',
    'business_operation_state',
    'business_operation_city',
    'business_operation_pin',
    'gstin',
    'promoter_pan',
    'promoter_pan_name',
    'company_cin',
    'company_pan',
    'company_pan_name',
  ],
  3: ['bank_branch_ifsc', 'bank_account_number', 'bank_account_name'],
  4: [
    'business_proof_url',
    'business_pan_url',
    'address_proof_url',
    'promoter_address_url',
    'form_12a_url',
    'form_80g_url',
  ],
};

// Used for marketplace linked accounts
const accountStepMap = {
  1: ['business_type', 'business_name'],
  2: ['bank_branch_ifsc', 'bank_account_number', 'bank_account_name'],
};

// Used for marketplace linked accounts that require KYC
const accountStepMapWithKYC = {
  1: ['business_type', 'business_name', 'company_pan', 'promoter_pan'],
  2: ['bank_branch_ifsc', 'bank_account_number', 'bank_account_name'],
  3: ['address_proof_url', 'promoter_pan_url'],
};

// We use only the keys except the last one from the activationStepMap
// the last key consists the file fields
const activationFields = Object.keys(activationStepMap).reduce((prev, curr) => {
  if (curr < Object.keys(activationStepMap).length) {
    prev.push(...activationStepMap[curr]);
  }
  return prev;
}, []);

const getFileDetails = (data) => {
  const fileFieldNameMapping = {
    business_proof_url: 'business_proof',
    business_operation_proof_url: 'business_operation_proof',
    business_pan_url: 'business_pan_proof',
    address_proof_url: 'address_proof',
    promoter_proof_url: 'promoter_proof',
    promoter_pan_url: 'promoter_pan_proof',
    promoter_address_url: 'promoter_address_proof',
    form_12a_url: 'ngo_12a_proof',
    form_80g_url: 'ngo_80g_proof',
  };

  let fileDetails = [];
  for (let key in fileFieldNameMapping) {
    if (data.hasOwnProperty(key) && data[key] !== null) {
      fileDetails.push(fileFieldNameMapping[key]);
    }
  }
  return fileDetails;
};

export default class Activation extends Entity {
  resourceFields = activationFields;

  fetch() {
    let params = {
      url: 'merchant/activation',
      mode: 'live',
      accountId: this.accountId,
    };

    return merchantFetch(params).then((response) => {
      response.data = this.getActivation(response.data);
      return new Activation(response.data);
    });
  }

  getActivation(data) {
    data.bank_account_number_confirmation = data.bank_account_number;
    data.submitted = data.submitted ? 1 : 0;
    data.locked = data.locked ? 1 : 0;
    data.activated = data.activated ? 1 : 0;
    data.files = getFileDetails(data);

    let linkedAccountKyc = data.need_kyc || 0;
    let stepMap = {};
    if (this.accountId) {
      stepMap = linkedAccountKyc ? accountStepMapWithKYC : accountStepMap;
    } else {
      stepMap = activationStepMap;
    }

    let steps = Object.keys(stepMap).map((step) => +step);
    if (data.can_submit) {
      data.steps_finished = steps;
    } else {
      let requiredFields =
        typeof data.verification !== 'undefined' &&
        typeof data.verification.required_fields !== 'undefined'
          ? data.verification.required_fields
          : [];

      let unfinishedSteps = requiredFields.reduce((prev, curr) => {
        let step = steps.find((step) => stepMap[step].indexOf(curr) > -1);
        if (step) prev.push(parseInt(step));
        return prev;
      }, []);

      //unique items needed & convert set to array.
      unfinishedSteps = uniqueArray(unfinishedSteps);

      if (unfinishedSteps.length) {
        data.steps_finished = arrayDiff(steps, unfinishedSteps);
      }
    }
    return data;
  }

  saveStep() {
    let data = this.serialize();
    return this.saveActivation(data);
  }

  submit() {
    let data = {
      submit: 1,
    };
    return this.saveActivation(data);
  }

  saveActivation(data) {
    // Auto add 'http' if not filled by user
    if (data.business_website) {
      data.business_website = autoPrefixUrls(data.business_website);
    }

    return merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      method: 'post',
      data,
      accountId: this.accountId,
    });
  }

  serializeProperty(prop) {
    if (prop === 'business_international') {
      return normalizeBoolean(this.business_international);
    }

    // remove all white spaces from multiple email inputs
    if (prop === 'transaction_report_email' && this.transaction_report_email) {
      return trim(this.transaction_report_email);
    }

    return super.serializeProperty(prop);
  }
}
