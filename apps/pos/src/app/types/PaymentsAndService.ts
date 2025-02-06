import { FileItem } from 'apps/pos/src/app/types/fileUpload';

export enum PaymentMethodFormType {
  'DIRECT' = 'direct',
  'AGGREGATOR' = 'aggregator',
}

interface PaymentMethodFormValue {
  checked: boolean;
  defaultValue: string;
  isRequired: boolean;
  isDisabled: boolean;
  isHidden: boolean;
  description: string;
  title: string;
  shouldShowCheckbox?: boolean;
  shouldShowValueInput?: boolean;
}

export interface PaymentMethodFormStringValue extends PaymentMethodFormValue {
  value: string;
}
export interface PaymentMethodFormNumberValue extends PaymentMethodFormValue {
  value: number;
}
export interface PaymentMethodFormBooleanValue extends PaymentMethodFormValue {
  value: boolean;
}
export interface PaymentMethodFormDocumentValue extends PaymentMethodFormValue {
  value: FileItem[];
}

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
  BRAND_EMI_CC_RATE_FIELD = 'brand_emi_cc_rate_field',
  BRAND_EMI_DC_RATE_FIELD = 'brand_emi_dc_rate_field',
  EMI_PLUS_CC_RATE_FIELD = 'emi_plus_cc_rate_field',
  EMI_PLUS_DC_RATE_FIELD = 'emi_plus_dc_rate_field',
  BRAND_EMI_RATE_ENABLED_FIELD = 'brand_emi_rate_enabled_field',
  EMI_PLUS_RATE_ENABLED_FIELD = 'emi_plus_rate_enabled_field',
}

export enum PricingStepComponents {
  MDR_VAS_RATES_COMPONENT = 'mdr_vas_rates_component',
  VAS_RATES_COMPONENT = 'vas_rates_component',
  BRAND_EMI_COMPONENT = 'brand_emi_component',
  ACQUISITION_MODEL_COMPONENT = 'acquisition_model_component',
}
export type DirectModelForm = {
  [PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.CUSTOM_RATES_DOCUMENTS_FIELD]: PaymentMethodFormDocumentValue;
  [PaymentMethodsFieldKeyNames.EMI_PLUS_RATE_ENABLED_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.BRAND_EMI_RATE_ENABLED_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.BRAND_EMI_CC_RATE_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.BRAND_EMI_DC_RATE_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.EMI_PLUS_CC_RATE_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.EMI_PLUS_DC_RATE_FIELD]: PaymentMethodFormStringValue;
};

export type AggregatorModelForm = DirectModelForm & {
  [PaymentMethodsFieldKeyNames.DEBIT_CARD_RUPAY_MDR_RATE_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.DEBIT_CARD_VISA_MASTERCARD_MAESTRO_GREATER_THAN_2K_MDR_RATE_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.DEBIT_CARD_VISA_MASTERCARD_MAESTRO_LESS_THAN_2K_MDR_RATE_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.CREDIT_CARD_MDR_RATE_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.PREPAID_B2B_CORPORATE_CHANNEL_INTERNATIONAL_CARD_MDR_RATE_FIELD]: PaymentMethodFormStringValue;
  [PaymentMethodsFieldKeyNames.UPI_MDR_RATE_FIELD]: PaymentMethodFormStringValue;
};

export enum MODULAR_PRICING_FIELDS {
  PRICING_STEP = 'pricing_step',
  STORE_TYPE_FIELD = 'store_type_field',
  BRAND_NAME_FIELD = 'brand_name_field',
  DEALER_CODE_FIELD = 'dealer_code_field',
  DISTRIBUTOR_CODE_FIELD = 'distributor_code_field',
  STATE_CODE_FIELD = 'state_code_field',
  MERCHANT_GST_FIELD = 'merchant_gst_field',
  REMOVE_BRAND_DETAILS_FIELD = 'remove_brand_details_field',
  BRAND_DETAILS_FIELD = 'brand_details_field',
  VERIFICATION_STATUS_FIELD = 'verification_status_field',
  VERIFICATION_DETAILS_ID_FIELD = 'verification_details_id_field',
  FETCH_BRAND_DATA_FIELDS = 'fetch_brand_data_fields',
  FETCH_FIELDS_FOR_BRAND = 'fetch_fields_for_brand',
  BRAND_DETAILS_SUMMARY = 'brand_details_summary',
  RESET_BRAND_DETAILS_FIELD = 'reset_brand_details_field',
  ACQUISITION_MODEL_FIELD = 'acquisition_model_field',
  MODULAR_CALLBACK = 'modular_callback',
}
