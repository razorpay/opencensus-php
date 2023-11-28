import { PaymentMethod } from 'merchant/views/Transactions/v2/Payments/types';
export type SubmerchantPaymentMethod =
  | PaymentMethod
  | 'account'
  | 'bank_account'
  | 'customer'
  | 'fund_transfer'
  | 'vpa';

type PricingRule = {
  // static fields
  amount_range_max: string | null;
  amount_range_min: string | null;
  feature: string | null;
  fee_bearer: string | null;
  fee_model: string | null;
  fixed_rate: string | null;
  max_fee: string | null;
  min_fee: string | null;
  payment_issuer: string | null;
  payment_method: SubmerchantPaymentMethod;
  payment_method_subtype: string | null;
  payment_method_type: string | null;
  payment_network: string | null;
  percent_rate: number | null;
  percent_rate_scale_factor: number | null;
  procurer: string | null;
  product: string | null;
  type: string | null;
  // dynamic fields
  account_type?: string | null;
  auth_type?: string | null;
  channel?: string | null;
  emi_duration?: string | null;
  international?: string | null;
  payouts_filter?: string | null;
  receiver_type?: string | null;
};

export type PartnerPricingPlans = {
  id: string;
  name: string;
  entity: string;
  org_id: string;
  count: number;
  rules: Array<PricingRule>;
};
