export type CustomerWhitelist =
  | {
      key: string;
      segment_ids: string[];
    }
  | Record<string, unknown>;

export interface Condition {
  cart?: {
    quantity?: {
      threshold: number;
      op: 'gte';
    };
    amount?: {
      threshold: number;
      op: 'gte';
    };
  };
}

export interface Redemption {
  type: string;
  fee_discount: {
    type: string;
    price: {
      formula: {
        coeff: number;
        const: number;
      };
      max_unit_discount: number;
    };
  };
}

export interface EvaluateRule {
  condition: Condition;
  redemption: Redemption;
}

export interface CouponPayload {
  type: string;
  code: string;
  description: string;
  display: boolean;
  auto_apply: boolean;
  currency: string;
  active: string;
  expiry: string | null;
  budget: number;
  status: string;
  source: string;
  id: string;
  discover_rules: Condition[] | null;
  evaluate_rules: EvaluateRule[];
  usage: {
    customer_email: number | null;
    customer_mobile_no: number | null;
    total: number | null;
  };
  meta_data: {
    display_information: {
      couponDetails: CouponDetails;
      discountDetails: DiscountDetails;
      couponValidity: CouponValidity;
      couponEligibility: CouponEligibility;
      usageRestriction: UsageRestriction;
    };
  };
  customer_whitelist: CustomerWhitelist;
  disabled_methods: string[] | null;
}
