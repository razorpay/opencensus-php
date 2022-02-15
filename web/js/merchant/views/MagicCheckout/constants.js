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
