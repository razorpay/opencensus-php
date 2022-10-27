import { RULE_TYPES } from 'merchant/views/MagicCheckout/constants';

/*
  Function: transfeeRuleToApiFormat used to convert rupee to paise and change the structure to API required format
*/
export function transfeeRuleToApiFormat({ rule_type, flat, slabs }) {
  if (rule_type === RULE_TYPES.FLAT) {
    flat = flat * 100;
  } else if (rule_type === RULE_TYPES.SLABS) {
    slabs = slabs.map((slab) => ({
      gte: slab.gte * 100,
      lte: slab.lte * 100,
      fee: slab.fee * 100,
    }));
  }
  return { rule_type, flat, slabs };
}

/*
  Function: transfeeRuleToNormalFormat used to convert paise to rupee and change the structure to UI required format
*/
export function transfeeRuleToNormalFormat({ rule_type, flat, slabs }) {
  if (rule_type === RULE_TYPES.FLAT) {
    flat = flat / 100;
  } else if (rule_type === RULE_TYPES.SLABS) {
    slabs = slabs.map((slab) => ({
      gte: slab.gte / 100,
      lte: slab.lte / 100,
      fee: slab.fee / 100,
    }));
  }
  return { rule_type, flat, slabs };
}
