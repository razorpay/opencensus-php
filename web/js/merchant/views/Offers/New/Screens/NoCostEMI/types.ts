export interface EmiPlanType {
  duration: number;
  subvention: 'customer' | 'merchant';
  min_amount: number;
  interest: number;
  merchant_payback: string;
}

export interface NoCostOfferFormProps {
  tenure: EmiPlanType[];
  onChange: () => void;
  onOffersChange: () => void;
  formData: FormDataType;
  offersData: OfferStateType;
}

export interface LowCostOfferType {
  discount_to_avail: {
    discount_percentage: number;
    applicable_on?: null;
    applicable_values: null;
  };
  tenure: number;
  issuer: string;
}

export interface OfferStateType {
  [x: string]: {
    tenure: number;
    offer_type?: EMI_OFFER_TYPES;
    merchant_discount?: string;
    valid?: boolean;
  };
}

export interface EMITenureActionProps {
  plan: EmiPlanType;
  key: number;
  formData: FormDataType;
  onOffersChange: (offers: OfferStateType) => void;
  onChange: (payload: {
    target: { name: string; value: string | number[] | LowCostOfferType[] };
  }) => void;
  offersData: OfferStateType;
}

export interface FormDataType {
  issuer: string;
  emi_durations?: number[];
  low_cost_emi?: LowCostOfferType[];
}

export enum INPUT_VALIDATION_STATES {
  ERROR = 'error',
  SUCCESS = 'success',
  NONE = 'none',
}

export enum EMI_OFFER_TYPES {
  LOW_COST = 'low_cost',
  NO_COST = 'no_cost',
  NONE = '',
}

export enum offerStateKeys {
  TENURE = 'tenure',
  OFFER_TYPE = 'offer_type',
  MERCHANT_DISCOUNT = 'merchant_discount',
}

export type experimentType = {
  variables: { result: string };
};
