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
    for (let step in stepMap) {
      steps.push(parseInt(step));
    }
    if (data.can_submit === true) {
      data.steps_finished = steps;
    } else {
      let unfinishedSteps = [];
      let requiredFields = typeof data['verification'] !== 'undefined' &&
        typeof data['verification']['required_fields'] !== 'undefined'
        ? data['verification']['required_fields']
        : [];

      for (let i = 0; i < requiredFields.length; i++) {
        let key = requiredFields[i];
        for (let step in stepMap) {
          let fields = stepMap[step];
          if (fields.indexOf(key) > -1) {
            unfinishedSteps.push(parseInt(step));
          }
        }
      }
      unfinishedSteps = new Set(unfinishedSteps).toJSON();

      if (unfinishedSteps.length !== 0) {
        data.steps_finished = arrayDiff(steps, unfinishedSteps);
      }
    }

    return data;
  }

  getFieldsToStepMap() {
    const stepMap = {
      1: [
        'contact_name',
        'contact_email',
        'transaction_report_email',
        'contact_mobile',
        'contact_landline',
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
      ],
      3: [
        'business_website',
        'website_about',
        'website_contact',
        'website_privacy',
        'website_terms',
        'website_refund',
        'website_pricing',
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
      ],
      5: [
        'business_proof_url',
        'business_pan_url',
        'address_proof_url',
        'promoter_address_url',
      ],
    };

    const stepMapAccount = {
      1: ['business_type', 'business_name', 'company_pan', 'promoter_pan'],
      2: [
        'bank_branch_ifsc',
        'bank_account_number',
        'bank_account_type',
        'bank_account_name',
      ],
      3: ['address_proof_url', 'promoter_pan_url'],
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
