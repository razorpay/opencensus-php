import Entity from './Entity';
import ajax from 'merchant/utils/ajax';
import { normalizeBoolean, isBlank, arrayDiff } from 'rzp/utils/rzp-utils';

// Used for Activation
const activationStepMap = {
  1: [
    'contact_name',
    'contact_email',
    'transaction_report_email',
    'contact_mobile',
    'contact_landline',
    'role',
    'department',
  ],
  2: [
    'business_type',
    'business_name',
    'business_dba',
    'business_international',
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
    'business_doe',
    'transaction_volume',
    'transaction_value',
    'gstin',
    'p_gstin',
    'promoter_pan',
    'promoter_pan_name',
    'company_cin',
    'company_pan',
    'company_pan_name',
  ],
  3: [
    'business_website',
    'website_about',
    'website_contact',
    'website_privacy',
    'website_terms',
    'website_refund',
    'website_pricing',
    'website_login',
  ],
  4: [
    'bank_branch_ifsc',
    'bank_account_number',
    'bank_account_type',
    'bank_account_name',
    'bank_beneficiary_address1',
    'bank_beneficiary_address2',
    'bank_beneficiary_address3',
    'bank_beneficiary_city',
    'bank_beneficiary_state',
    'bank_beneficiary_pin',
    'bank_branch',
  ],
  5: [
    'business_proof_url',
    'business_pan_url',
    'address_proof_url',
    'promoter_address_url',
  ],
};

// Used for marketplace linked accounts
const accountStepMap = {
  1: ['business_type', 'business_name'],
  2: [
    'bank_branch_ifsc',
    'bank_account_number',
    'bank_account_type',
    'bank_account_name',
  ],
};

// Used for marketplace linked accounts that require KYC
const accountStepMapWithKYC = {
  1: ['business_type', 'business_name', 'company_pan', 'promoter_pan'],
  2: [
    'bank_branch_ifsc',
    'bank_account_number',
    'bank_account_type',
    'bank_account_name',
  ],
  3: ['address_proof_url', 'promoter_pan_url'],
};

// We use only the first four keys from the activationStepMap
// the fifth key consists the file fields
const activationFields = Object.keys(activationStepMap).reduce((prev, curr) => {
  if (curr < 5) {
    prev.push(...activationStepMap[curr]);
  }
  return prev;
}, []);

const getFileDetails = data => {
  const fileFieldNameMapping = {
    business_proof_url: 'business_proof',
    business_operation_proof_url: 'business_operation_proof',
    business_pan_url: 'business_pan_proof',
    address_proof_url: 'address_proof',
    promoter_proof_url: 'promoter_proof',
    promoter_pan_url: 'promoter_pan_proof',
    promoter_address_url: 'promoter_address_proof',
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
    let activationData = {
      route_name: 'merchant_activation_details',
    };

    if (this.accountId) {
      activationData.account_id = this.accountId;
    }

    return ajax({
      url: '/user/generic',
      appendModeInURL: false,
      data: activationData,
    }).then(response => {
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

    let steps = Object.keys(stepMap).map(step => +step);
    if (data.can_submit) {
      data.steps_finished = steps;
    } else {
      let requiredFields =
        typeof data.verification !== 'undefined' &&
        typeof data.verification.required_fields !== 'undefined'
          ? data.verification.required_fields
          : [];

      let unfinishedSteps = requiredFields.reduce((prev, curr) => {
        let step = steps.find(step => stepMap[step].indexOf(curr) > -1);
        prev.push(step);
        return prev;
      }, []);

      unfinishedSteps = new Set(unfinishedSteps).toJSON();
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
    let activationData = {
      route_name: 'merchant_activation_save',
      body: data,
    };

    if (this.accountId) {
      activationData.account_id = this.accountId;
    }

    return ajax({
      url: '/user/generic',
      method: 'post',
      appendModeInURL: false,
      data: activationData,
    });
  }

  serializeProperty(prop) {
    // The below fields should not be sent if they are not set, as the api expects them only when they are set
    if (
      [
        'business_international',
        'transaction_volume',
        'transaction_value',
      ].indexOf(prop) !== -1 &&
      isBlank(this[prop])
    ) {
      return undefined;
    }

    if (prop === 'business_international') {
      return normalizeBoolean(this.business_international);
    }

    return super.serializeProperty(prop);
  }
}
