export enum MODULAR_ADDITIONAL_DETAILS_FIELDS {
  STEP_NAME = 'additional_details_step',
  ADDITIONAL_DETAILS_COMPONENT = 'additional_details_component',
  OMC_FIELD = 'additional_details_omc_field',
  SAP_CODE_FIELD = 'additional_details_sap_code_field',
  ADDITIONAL_DETAILS_STEP = 'additional_details_step',
  PHONE_NUMBER_FIELD = 'additional_details_cashier_mobile_number_field',

  PARTNER_ADDITIONAL_DETAILS_COMPONENT = 'partner_additional_details_component',
  ACQUIRER_PREFERENCE_FIELD = 'additional_details_acquirer_preference_field',
}

export type FieldRules = {
  required: boolean;
  minLength?: number;
  maxLength?: number;
  pattern?: RegExp;
};
