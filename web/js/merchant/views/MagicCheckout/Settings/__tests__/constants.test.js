import { TABS } from 'merchant/views/MagicCheckout/Settings/constants';

describe('testing constants', () => {
  test('condition should return true if feature is enabled', () => {
    const user = {
      isMagicCODOrderAutomationEnabled: true,
      isMagicPrepayCODEnabled: true,
      isMagicCODEngineEnabled: true,
      role: 'owner',
    };

    const abExperiments = {
      magic_analytics_setting: { variables: { result: 'on' } },
      magic_shopify_shipping_engine: { variables: { result: 'on' } },
    };

    Object.keys(TABS).forEach((platform) => {
      TABS[platform].forEach((item) => {
        if (item.condition) {
          expect(item.condition(user, abExperiments)).toBeTruthy();
        }
      });
    });
  });

  test('condition should return false if feature is not enabled', () => {
    const user = {
      isMagicCODOrderAutomationEnabled: false,
      isMagicPrepayCODEnabled: false,
      role: 'manager',
    };
    Object.keys(TABS).forEach((platform) => {
      TABS[platform].forEach((item) => {
        if (item.condition) {
          expect(item.condition(user)).toBeFalsy();
        }
      });
    });
  });
});
