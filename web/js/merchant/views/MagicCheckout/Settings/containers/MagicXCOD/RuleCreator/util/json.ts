import type { ShopifyRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

const defaultShopifyRule: ShopifyRule = {
  rules: [
    {
      conditions: {
        all: [
          {
            all: [{ fact: '', op: 'eq', val: '' }],
          },
        ],
      },
      actions: [{ type: '' }],
    },
  ],
  ruleFacts: {},
};

export const toString: (json: any) => string = (json = {}) => {
  return JSON.stringify(json);
};

export const toJSON = (jsonString: string): ShopifyRule => {
  try {
    return JSON.parse(jsonString);
  } catch (err) {
    // if the parsing fails, we return default shopify rule
    // which helps use it as default value when initiating state
    return defaultShopifyRule;
  }
};
