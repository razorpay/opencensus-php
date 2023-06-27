import { rupeesToPaise, paiseToRupees } from 'common/utils/rzp-utils';

export const formatRulesToSlabs = (feeRules = []) => {
  return feeRules.map((feeRule) => ({
    fee: paiseToRupees(feeRule.fee),
    id: feeRule.id,
    lte: paiseToRupees(feeRule.rule.order_amount.lte),
    gte: paiseToRupees(feeRule.rule.order_amount.gte),
    error: {
      lte: '',
      gte: '',
      fee: '',
    },
  }));
};

export const formatSlabsToFeeRules = (slabs = []) => {
  return slabs.map((slab) => ({
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
  }));
};
export const checkForExistingRule = ({ rule }, storeRules) => {
  return storeRules?.find(
    (r) =>
      r.rule.order_amount.lte === rule.order_amount.lte &&
      r.rule.order_amount.gte === rule.order_amount.gte,
  );
};

export const findRuleInRange = ({ rule, id }, storeRules) => {
  return storeRules?.find(
    (r) =>
      r.id !== id &&
      rule.order_amount.lte > r.rule.order_amount.gte &&
      rule.order_amount.lte <= r.rule.order_amount.lte &&
      rule.order_amount.gte > r.rule.order_amount.gte &&
      rule.order_amount.gte <= r.rule.order_amount.lte,
  );
};
export const getRangedRules = (newRules, storeRules = []) => {
  return newRules.map((rule) => {
    if (!rule.id) {
      const existingRule = checkForExistingRule(rule, storeRules);
      if (existingRule) {
        rule.id = existingRule.id;
      }
    }
    return rule;
  });
};
