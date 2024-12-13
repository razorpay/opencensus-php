import { formatter as shopifyFormatter } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/formatRule/shopify';

import type {
  Rule,
  ShopifyRule,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

interface FormatRuleFunction {
  (rule: Rule): ShopifyRule;
}
export const formatRule: FormatRuleFunction = (rule) => {
  return shopifyFormatter(rule);
};
