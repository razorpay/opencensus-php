import Entity from './Entity';
import ajax from 'merchant/utils/ajax';
import { normalizeBoolean, isBlank } from 'rzp/utils/rzp-utils';

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
    return ajax({
      url: `/activation/details/${this.accountId}`,
      appendModeInURL: false,
    }).then(response => {
      response.data.bank_account_number_confirmation =
        response.data.bank_account_number;
      return new Activation(response.data);
    });
  }

  saveStep(step) {
    let data = this.serialize();
    return ajax({
      url: '/user/generic',
      method: 'post',
      appendModeInURL: false,
      data: {
        route_name: 'merchant_activation_save',
        body: data,
      },
    });
  }

  submit() {
    let data = {
      submit: true,
    };
    return ajax({
      url: '/user/generic',
      method: 'post',
      appendModeInURL: false,
      data: {
        route_name: 'merchant_activation_save',
        body: data,
      },
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
