export enum PaymentMethodFormType {
  'DIRECT' = 'direct',
  'AGGREGATOR' = 'aggregator',
}

export type PaymentMethodFormValue = {
  checked: boolean;
  value: string | number | boolean | Array<any>;
  defaultValue: string;
  isRequired: boolean;
  isDisabled: boolean;
  isHidden: boolean;
  description: string;
  title: string;
};

export const enum PaymentMethodsFieldKeyNames {
  DEBIT_CARD_RUPAY_MDR_RATE_FIELD = 'debit_card_rupay_mdr_rate_field',
  DEBIT_CARD_VISA_MASTERCARD_MAESTRO_GREATER_THAN_2K_MDR_RATE_FIELD = 'debit_card_visa_mastercard_maestro_greater_than_2k_mdr_rate_field',
  DEBIT_CARD_VISA_MASTERCARD_MAESTRO_LESS_THAN_2K_MDR_RATE_FIELD = 'debit_card_visa_mastercard_maestro_less_than_2k_mdr_rate_field',
  CREDIT_CARD_MDR_RATE_FIELD = 'credit_card_mdr_rate_field',
  PREPAID_B2B_CORPORATE_CHANNEL_INTERNATIONAL_CARD_MDR_RATE_FIELD = 'prepaid_b2b_corporate_channel_international_card_mdr_rate_field',
  UPI_MDR_RATE_FIELD = 'upi_mdr_rate_field',
  VAS_CC_EMI_RATE_FIELD = 'vas_cc_emi_rate_field',
  VAS_DC_EMI_RATE_FIELD = 'vas_dc_emi_rate_field',
  VAS_CC_EMI_RATE_ENABLED_FIELD = 'vas_cc_emi_rate_enabled_field',
  VAS_DC_EMI_RATE_ENABLED_FIELD = 'vas_dc_emi_rate_enabled_field',
  CUSTOM_RATES_DOCUMENTS_FIELD = 'custom_rates_documents_field',
  PREVIOUS_CUSTOM_RATES_DOCUMENTS_FIELD = 'previous_custom_rates_documents_field',
  CUSTOM_RATES_ENABLED_FIELD = 'custom_rates_enabled_field',
  MDR_VAS_PRICING_FIELD = 'mdr_vas_pricing_field',
  CUSTOM_PRICING_PROOF = 'custom_pricing_proof',
}

export enum PricingStepComponents {
  MDR_VAS_RATES_COMPONENT = 'mdr_vas_rates_component',
  VAS_RATES_COMPONENT = 'vas_rates_component',
}
export type DirectModelForm = {
  [PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD]: PaymentMethodFormValue;
  [PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD]: PaymentMethodFormValue;
  [PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD]: PaymentMethodFormValue;
  [PaymentMethodsFieldKeyNames.CUSTOM_RATES_ENABLED_FIELD]: PaymentMethodFormValue;
};

export type AggregatorModelForm = DirectModelForm & {
  [PaymentMethodsFieldKeyNames.DEBIT_CARD_RUPAY_MDR_RATE_FIELD]: PaymentMethodFormValue;
  [PaymentMethodsFieldKeyNames.DEBIT_CARD_VISA_MASTERCARD_MAESTRO_GREATER_THAN_2K_MDR_RATE_FIELD]: PaymentMethodFormValue;
  [PaymentMethodsFieldKeyNames.DEBIT_CARD_VISA_MASTERCARD_MAESTRO_LESS_THAN_2K_MDR_RATE_FIELD]: PaymentMethodFormValue;
  [PaymentMethodsFieldKeyNames.CREDIT_CARD_MDR_RATE_FIELD]: PaymentMethodFormValue;
  [PaymentMethodsFieldKeyNames.PREPAID_B2B_CORPORATE_CHANNEL_INTERNATIONAL_CARD_MDR_RATE_FIELD]: PaymentMethodFormValue;
  [PaymentMethodsFieldKeyNames.UPI_MDR_RATE_FIELD]: PaymentMethodFormValue;
};
