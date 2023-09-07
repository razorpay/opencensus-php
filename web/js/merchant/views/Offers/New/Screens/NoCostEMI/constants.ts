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
    value: 'Effective Interest',
  },
  {
    key: 'offer_type',
    value: 'EMI Offer Type',
  },
  {
    key: 'merchant_interest',
    value: 'Interest % borne by merchant (you)',
  },
  {
    key: 'customer_interest',
    value: 'Interest % borne by your customer',
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
};
