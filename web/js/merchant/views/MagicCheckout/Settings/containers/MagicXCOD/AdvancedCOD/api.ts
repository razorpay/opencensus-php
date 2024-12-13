import { merchantFetch } from 'merchant/utils/ajax';

import type { Rule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';

export const createRule = async (rule: Partial<Rule>, merchantId: string) => {
  rule.merchant_id = merchantId;
  return merchantFetch({
    url: 'magic/sopc/customisations/rules',
    method: 'post',
    data: rule,
  });
};

export const updateRule = async (rule: Partial<Rule>, merchantId: string) => {
  rule.merchant_id = merchantId;
  return merchantFetch({
    url: `magic/sopc/customisations/rules/${rule.id}`,
    method: 'put',
    data: rule,
  });
};

export const deleteRule = async (rule: Rule, merchantId: string) => {
  rule.merchant_id = merchantId;
  return merchantFetch({
    url: `magic/sopc/customisations/rules/${rule.id}`,
    method: 'delete',
  });
};

export const api = {
  createRule,
  updateRule,
  deleteRule,
};
