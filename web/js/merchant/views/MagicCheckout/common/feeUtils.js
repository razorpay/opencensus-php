import { RULE_TYPES } from 'merchant/views/MagicCheckout/ShippingServices/constants';

const validators = {
  flat: (value) => {
    if (typeof value === 'undefined') {
      return 'Enter a value';
    }
    return '';
  },
  lte: (slabs, index) => (value) => {
    if (slabs[index].gte > parseInt(value, 10)) {
      return 'Invalid order value';
    }
    return '';
  },
};

export function isFeeRuleValid(feeRule) {
  if (feeRule.rule_type === RULE_TYPES.FLAT) {
    const error = validators.flat(feeRule.flat);
    return !error;
  }

  if (feeRule.rule_type === RULE_TYPES.SLABS) {
    const error = feeRule.slabs.reduce((accumulator, slab, index) => {
      return accumulator || validators.lte(feeRule.slabs, index)(slab.lte);
    }, '');
    return !error;
  }

  return true;
}

export default validators;
