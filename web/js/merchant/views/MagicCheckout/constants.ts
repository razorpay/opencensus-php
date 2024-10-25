export const MAGIC_CHECKOUT_STATUS = {
  AVAILABLE: 'available',
  INTERESTED: 'interested',
  WAITLISTED: 'waitlisted',
  LIVE: 'live',
  DEACTIVATED: 'deactivated',
};

export const FEE_RULES = {
  COD_FEE_RULE: 'cod_fee_rule',
  SHIPPING_FEE_RULE: 'shipping_fee_rule',
};

export const RULE_TYPES = {
  FLAT: 'flat',
  SLABS: 'slabs',
  FREE: 'free',
};

export const DEFAULT_RULE = {
  rule_type: 'free',
  flat: 0,
  slabs: [{ gte: 0, lte: 0, fee: 0 }],
};

export const PLATFORMS: Record<string, string> = {
  SHOPIFY: 'shopify',
  WOOCOMMERCE: 'woocommerce',
  NATIVE: 'native',
} as const;

export const ACCESS_ROLES = ['owner', 'admin'];

export const MAGIC_DOC_LINK =
  'https://razorpay.com/docs/payments/payment-pages/plugins-add-ons/magic-checkout/';

export const MAGIC_DASHBOARD_REVAMP_EXPERIMENT = 'magic_dashboard_revamp';
export const MAGICX_PUBLICAPP_COD_EXPERIMENT = 'magicx_publicapp_cod';
