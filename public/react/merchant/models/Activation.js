import Entity from './Entity';
import ajax from 'merchant/utils/ajax';
import { normalizeBoolean, isBlank, arrayDiff } from 'rzp/utils/rzp-utils';

const activationFields = [
  'contact_name',
  'contact_email',
  'contact_mobile',
  'contact_landline',
  'business_type',
  'business_name',
  'business_dba',
  'business_website',
  'business_paymentdetails',
  'business_registered_address',
  'business_registered_state',
  'business_registered_city',
  'business_registered_pin',
  'business_operation_address',
  'business_operation_state',
  'business_operation_city',
  'business_operation_pin',
  'promoter_pan',
  'promoter_pan_name',
  'business_doe',
  'gstin',
  'p_gstin',
  'company_cin',
  'company_pan',
  'company_pan_name',
  'business_model',
  'transaction_volume',
  'transaction_value',
  'website_about',
  'website_contact',
  'website_privacy',
  'website_terms',
  'website_refund',
  'website_pricing',
  'website_login',
  'transaction_report_email',
  'bank_account_number',
  'bank_account_name',
  'bank_account_type',
  'bank_branch',
  'bank_branch_ifsc',
  'bank_beneficiary_address1',
  'bank_beneficiary_address2',
  'bank_beneficiary_address3',
  'bank_beneficiary_city',
  'bank_beneficiary_state',
  'bank_beneficiary_pin',
  'role',
  'department',
  'business_international',
];

export default class Activation extends Entity {
  resourceFields = activationFields;

  fetch() {
    let activationData = {
      route_name: 'merchant_activation_details',
    };

    if (this.accountId) {
      activationData['account_id'] = this.accountId;
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

  getFileDetails(data) {
    let fileFieldNameMapping = {
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
  }

  getActivation(data) {
    data.bank_account_number_confirmation = data.bank_account_number;
    data.submitted = data.submitted ? 1 : 0;
    data.locked = data.locked ? 1 : 0;
    data.activated = data.activated ? 1 : 0;
    data.files = this.getFileDetails(data);

    let stepMap = this.getFieldsToStepMap();
    let steps = [];
    for (let key in stepMap) {
      steps.push(stepMap[key]);
    }
    steps = new Set(steps).toJSON();
    if (data.can_submit === true) {
      data.steps_finished = steps;
    } else {
      let unfinishedSteps = [];
      let requiredFields = typeof data['verification']['required_fields'] !==
        'undefined'
        ? data['verification']['required_fields']
        : [];

      for (let i = 0; i < requiredFields.length; i++) {
        let key = requiredFields[i];
        if (stepMap.hasOwnProperty(key)) {
          unfinishedSteps.push(stepMap[key]);
        }
      }

      if (unfinishedSteps.length !== 0) {
        unfinishedSteps = new Set(unfinishedSteps).toJSON();
        data.steps_finished = arrayDiff(steps, unfinishedSteps);
      }
    }

    return data;
  }

  getFieldsToStepMap() {
    const stepMap = {
      contact_name: 1,
      contact_email: 1,
      transaction_report_email: 1,
      contact_mobile: 1,
      contact_landline: 1,

      business_type: 2,
      business_name: 2,
      business_dba: 2,
      business_international: 2,
      business_paymentdetails: 2,
      business_model: 2,
      business_registered_address: 2,
      business_registered_state: 2,
      business_registered_city: 2,
      business_registered_pin: 2,
      business_operation_address: 2,
      business_operation_state: 2,
      business_operation_city: 2,
      business_operation_pin: 2,
      business_doe: 2,
      transaction_volume: 2,
      transaction_value: 2,
      gstin: 2,
      p_gstin: 2,
      promoter_pan: 2,
      promoter_pan_name: 2,

      business_website: 3,
      website_about: 3,
      website_contact: 3,
      website_privacy: 3,
      website_terms: 3,
      website_refund: 3,
      website_pricing: 3,

      bank_branch_ifsc: 4,
      bank_account_number: 4,
      bank_account_type: 4,
      bank_account_name: 4,
      bank_beneficiary_address1: 4,
      bank_beneficiary_address2: 4,
      bank_beneficiary_address3: 4,
      bank_beneficiary_city: 4,
      bank_beneficiary_state: 4,
      bank_beneficiary_pin: 4,

      business_proof_url: 5,
      business_pan_url: 5,
      address_proof_url: 5,
      promoter_address_url: 5,
    };

    const stepMapAccount = {
      business_type: 1,
      business_name: 1,
      company_pan: 1,
      promoter_pan: 1,

      bank_branch_ifsc: 2,
      bank_account_number: 2,
      bank_account_type: 2,
      bank_account_name: 2,

      address_proof_url: 3,
      promoter_pan_url: 3,
    };

    if (this.accountId) {
      return stepMapAccount;
    } else {
      return stepMap;
    }
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
      activationData['account_id'] = this.accountId;
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
