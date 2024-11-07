import {
  CUSTOMER_RISK_CATEGORY,
  CustomerRiskCategory,
  PARTIAL_COD_TYPE,
  PartialCODConfigs,
  PREPAID_PAYMENY_AMOUNT_ITEM_TYPE,
  PrepaidPaymentAmountItem,
} from 'merchant/views/MagicCheckout/PartialCOD/types';

export const NOTIFICATION_MSGS = {
  error: 'Something went wrong, please try again after sometime.',
  slabSavedSuccess: 'Slab saved successfully.',
  slabUpdatedSuccess: 'Slab updated successfully.',
  slabRemovedSuccess: 'Slab deleted successfully.',
  disableSuccess: 'Partial COD disabled successfully.',
  percentValAbove50: 'Percentage value should be between 0 and 50',
  'The configs.prepaid payment amount field is required when configs is present.':
    'Please set values in order to enable Partial COD',
};

export const riskCategories: CustomerRiskCategory[] = [
  CUSTOMER_RISK_CATEGORY.LOW,
  CUSTOMER_RISK_CATEGORY.MEDIUM,
  CUSTOMER_RISK_CATEGORY.HIGH,
];

export const NEW_PREPAID_AMOUNT_ITEM: PrepaidPaymentAmountItem = {
  type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT,
  value: 0,
  rules: {
    min_order_amount: 0,
    max_order_amount: 0,
    customer_risk_category: [],
  },
};

export const NEW_PARTIAL_COD_CONFIGS: PartialCODConfigs = {
  type: PARTIAL_COD_TYPE.BASIC,
  prepaid_payment_amount: [],
};

//these are a defualt values for the basic slab
export const DEFAULT_BASIC_SLAB_VALUES = [
  { type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT, value: 9900 }, // ₹99
  { type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT, value: 19900 }, // ₹199
  { type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE, value: 5 }, // 5%
  { type: PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.PERCENTAGE, value: 10 }, // 10%
];
