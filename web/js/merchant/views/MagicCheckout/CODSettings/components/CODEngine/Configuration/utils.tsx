import { getFormattedAmountNew } from 'common/utils/rzp-utils';

export const formatFeeRuleRange = (fee_rule): { order_range: string; fee: string } => {
  return {
    order_range: `${getFormattedAmountNew(
      fee_rule?.rule?.order_amount?.gte || 0,
      true,
    )} - ${getFormattedAmountNew(fee_rule?.rule?.order_amount?.lte, true)}`,
    fee: getFormattedAmountNew(fee_rule?.fee, true),
  };
};

export const findRulesInRange = ({ rule, id }, storeRules) => {
  return storeRules?.filter(
    (r) =>
      r.id !== id &&
      rule.order_amount.lte >= r.rule.order_amount.gte &&
      rule.order_amount.gte <= r.rule.order_amount.lte,
  );
};
