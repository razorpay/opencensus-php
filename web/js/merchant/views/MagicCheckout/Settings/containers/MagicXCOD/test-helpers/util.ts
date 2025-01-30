import { toJSON } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/json';
import { parseShopifyRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/parse';
import { actionTypesToText } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/Table/constants';

import type { Rule as ACODRule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

export const rulesToTableNodes = (rules: ACODRule[]) => {
  return rules.map((rule) => {
    const node = { ...rule, about: '' };
    try {
      const parsedRule = parseShopifyRule(toJSON(rule?.rule));
      const actionType = parsedRule.actions[0].type;
      node.about = actionTypesToText[actionType];
    } catch (_) {
      node.about = '';
    }
    return node;
  });
};
