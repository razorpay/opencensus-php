import {
  AggregatorModelForm,
  DirectModelForm,
  PaymentMethodsFieldKeyNames,
} from 'apps/pos/src/app/types/PaymentsAndService';

export const DirectModelFormKeys: (keyof DirectModelForm)[] = [
  PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_FIELD,
  PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_FIELD,
];

export const AggregatorModelFormKeys: (keyof AggregatorModelForm)[] = [
  PaymentMethodsFieldKeyNames.DEBIT_CARD_RUPAY_MDR_RATE_FIELD,
  PaymentMethodsFieldKeyNames.DEBIT_CARD_VISA_MASTERCARD_MAESTRO_GREATER_THAN_2K_MDR_RATE_FIELD,
  PaymentMethodsFieldKeyNames.DEBIT_CARD_VISA_MASTERCARD_MAESTRO_LESS_THAN_2K_MDR_RATE_FIELD,
  PaymentMethodsFieldKeyNames.CREDIT_CARD_MDR_RATE_FIELD,
  PaymentMethodsFieldKeyNames.PREPAID_B2B_CORPORATE_CHANNEL_INTERNATIONAL_CARD_MDR_RATE_FIELD,
  PaymentMethodsFieldKeyNames.UPI_MDR_RATE_FIELD,
];

export const OPTIONAL_FIELDS = [
  PaymentMethodsFieldKeyNames.VAS_CC_EMI_RATE_ENABLED_FIELD,
  PaymentMethodsFieldKeyNames.VAS_DC_EMI_RATE_ENABLED_FIELD,
];

export const CHARGES_REGEX = /^\d{1,2}(\.\d+)?$/;
