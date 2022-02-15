import { RULE_TYPES } from 'merchant/views/MagicCheckout/constants';

/**
 * eg, 
 * Input: {
    rule_type: 'free',
    flat: 0,
    slabs: [{ gte: 0, lte: 0, fee: 0 }],
  }
  @returns {array} [{ amount: 0, fee: 0 }]
 */
export function transformToApiFormat({ rule_type, flat, slabs }) {
  let res = [{ amount: 0, fee: 0 }];
  if (rule_type === RULE_TYPES.FLAT) {
    res[0].fee = flat * 100;
  } else if (rule_type === RULE_TYPES.SLABS) {
    res = slabs.map((slab) => ({ amount: slab.gte * 100, fee: slab.fee * 100 }));
    if (slabs[slabs.length - 1].lte && slabs[slabs.length - 1].lte !== Infinity) {
      res.push({ amount: (slabs[slabs.length - 1].lte + 1) * 100, fee: 0 });
    }
  }
  return res;
}

/**
 * eg, 
  @param {array} input [{ amount: 0, fee: 0 }]
  @returns {object} {
    rule_type: 'free',
    flat: 0,
    slabs: [{ gte: 0, lte: 0, fee: 0 }],
  }
 */
export function transformToComponentFormat(input) {
  if (!input) return null;
  const res = { rule_type: RULE_TYPES.FREE, flat: 0, slabs: [{ gte: 0, lte: 0, fee: 0 }] };
  if (input.length === 1 && input[0].fee > 0) {
    res.rule_type = RULE_TYPES.FLAT;
    res.flat = Math.round(input[0].fee / 100);
  } else if (input.length > 1) {
    res.rule_type = RULE_TYPES.SLABS;
    input.sort((a, b) => a.amount - b.amount);
    res.slabs = input.reduce((output, slab, currentIndex, array) => {
      if (currentIndex < array.length - 1) {
        output.push({
          gte: Math.round(slab.amount / 100),
          lte: Math.round((array[currentIndex + 1].amount - 100) / 100),
          fee: Math.round(slab.fee / 100),
        });
      } else {
        output.push({
          gte: Math.round(slab.amount / 100),
          lte: Infinity,
          fee: Math.round(slab.fee / 100),
        });
      }
      return output;
    }, []);
  }
  return res;
}
