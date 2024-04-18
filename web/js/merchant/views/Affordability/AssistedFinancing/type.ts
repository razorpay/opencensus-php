import { config, emiBanks } from './constants';

export enum PaymentLinkModalType {
  SUCCESS = 'SUCCESS',
  FAILURE = 'FAILURE',
  NONE = 'NONE',
}

type ProcessingFeePlan = {
  type: 'fixed';
  amount: number;
};

type EmiOption = {
  duration: number;
  interest: number;
  subvention: 'customer';
  min_amount: number;
  merchant_payback: string;
  processing_fee_plan?: ProcessingFeePlan;
};

type EmiOptions = {
  [key: string]: EmiOption[];
};

type CardlessEmi = {
  [key: string]: boolean;
};

export type PaymentConfig = {
  cardless_emi: CardlessEmi;
  emi_options: EmiOptions;
};

type EmiPlan = {
  emiPlan: string;
  interest: string;
  totalPayable: string;
};
export type PaymentOption = {
  method: string;
  title: string;
  name: string;
  provider: string;
  image: string;
  notEligible: boolean;
  type?: string;
  emiPlan?: EmiPlan[];
};

export type PaymentOptions = PaymentOption[];

export type SendPaymentLinkReqData = {
  currency: string;
  amount: number;
  customer: {
    contact: string;
  };
  notify: {
    sms: boolean;
  };
  notes?: {
    notes: string;
  };
};

export const METHODS = {
  EMI: 'emi',
  CARDLESS_EMI: 'cardless_emi',
} as const;

const { EMI, CARDLESS_EMI } = METHODS;
export const AFFORDABILITY_METHODS = [EMI, CARDLESS_EMI] as const;
export type AffordabilityMethod = typeof AFFORDABILITY_METHODS[number];

/**
 * Types to store emi issuers and cardless emi providers
 */
export type EmiProvider = keyof typeof emiBanks;
export type CardlessEmiProvider = keyof typeof config;

/*
 * Type to store all possible providers that can be passed to eligility API
 */
export type EligibilityProviderType = EmiProvider | CardlessEmiProvider;

/**
 * Constant to store Eligibility status types
 */

export type ValueOf<T> = T[keyof T];

export enum ELIGIBILITY_STATUS {
  ELIGIBLE = 'eligible',
  INELIGIBLE = 'ineligible',
  FAILED = 'failed',
}

export type Instrument = {
  method: AffordabilityMethod;
  provider?: EligibilityProviderType;
  issuer?: EligibilityProviderType;
  eligibility_req_id?: string;
  eligibility: {
    status: ValueOf<typeof ELIGIBILITY_STATUS>;
    error?: {
      code: string;
      description: string;
      source: string;
      step: string;
      reason: string;
    };
  };
};

export type INSTRUMENT_PAYLOAD = {
  method: AffordabilityMethod;
  instrument: string;
  eligibility_status: ValueOf<typeof ELIGIBILITY_STATUS>;
  ineligibility_code?: string;
  ineligibility_desc?: string;
};

export type AvailableEmiPropType = {
  paymentLinkData: PaymentOption | null;
  shouldShowEmiMethods: boolean;
  setPaymentLinkData: (value: PaymentOption | null) => void;
  showPaymentLinkModal: () => void;
  merchantPaymentMethods: PaymentOptions;
};

export type SendPaymentLinkType = {
  orderAmount: string | undefined;
  mobileNumber: string;
  showPaymentLinkModal: boolean;
  setShowPaymentLinkModal: (value: boolean) => void;
  paymentLinkData: PaymentOption | null;
};

export type EnabledOptionsArray = {
  method: string;
  provider: string;
  type?: string;
};
