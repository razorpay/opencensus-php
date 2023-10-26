import { rupeesToPaise, paiseToRupees } from 'common/utils/rzp-utils';
import { COD_ENGINES } from './constants';
export const formatRulesToSlabs = (feeRules = [], isRCOD = false) => {
  return feeRules.map((feeRule) => {
    const slab = {
      fee: paiseToRupees(feeRule.fee),
      id: feeRule.id,
      lte: paiseToRupees(feeRule.rule.order_amount.lte),
      gte: paiseToRupees(feeRule.rule.order_amount.gte),
      error: {
        lte: '',
        gte: '',
        fee: '',
      },
    };

    if (isRCOD) slab.name = feeRule.name;

    return slab;
  });
};

export const formatSlabsToFeeRules = (slabs = [], isRCOD = false) => {
  return slabs.map((slab) => {
    const rule = {
      id: slab.id,
      fee_type: 'cod_fee',
      rule_type: 'slab',
      fee: slab.fee ? rupeesToPaise(slab.fee) : 0,
      rule: {
        order_amount: {
          lte: rupeesToPaise(slab.lte),
          gte: rupeesToPaise(slab.gte),
        },
      },
    };

    if (isRCOD) {
      rule.name = slab.name;
    }

    return rule;
  });
};
export const checkForExistingRule = ({ rule }, storeRules) => {
  return storeRules?.find(
    (r) =>
      r.rule.order_amount.lte === rule.order_amount.lte &&
      r.rule.order_amount.gte === rule.order_amount.gte,
  );
};

export const getRangedRules = (newRules, storeRules = [], isAdvancedEngine) => {
  return newRules.map((rule) => {
    if (!rule.id) {
      const existingRule = checkForExistingRule(rule, storeRules);
      if (existingRule && !isAdvancedEngine) {
        rule.id = existingRule.id;
      }
    }
    return rule;
  });
};

export const isBasicCODEngine = (engine) => engine === COD_ENGINES.BASIC;
