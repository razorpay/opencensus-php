import { formatter as shopifyFormatter } from './shopify';

import type { Rule, ShopifyRule } from '../../types';

interface FormatRuleFunction {
  (rule: Rule): ShopifyRule;
}
export const formatRule: FormatRuleFunction = (rule) => {
  return shopifyFormatter(rule);
};
