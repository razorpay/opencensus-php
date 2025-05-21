export const EMI_OFFER_TYPES = [
  {
    name: '',
    label: '--Select--',
  },
  {
    name: 'no_cost',
    label: 'No Cost',
  },
  {
    name: 'low_cost',
    label: 'Low Cost',
  },
];

export const DECIMAL_POINT_REGEX = '^[0-9]+(.[0-9][0-9]?)?$';

export const offerHeaders = [
  {
    key: 'tenure',
    value: 'EMI Tenure',
  },
  {
    key: 'interest',
    value: 'No Cost EMI Subvention',
  },
  {
    key: 'offer_type',
    value: 'EMI Offer Type',
  },
  {
    key: 'merchant_interest',
    value: 'Subvention % borne by you',
  },
  {
    key: 'customer_interest',
    value: 'Subvention % borne by customer',
  },
];

export const offerStateKeys = {
  TENURE: 'tenure',
  OFFER_TYPE: 'offer_type',
  MERCHANT_DISCOUNT: 'merchant_discount',
};

export const offerPayloadKeys = {
  emi_durations: 'emi_durations',
  low_cost_emi: 'low_cost_emi',
  plan_merchant_payback: 'plan_merchant_payback',
};
