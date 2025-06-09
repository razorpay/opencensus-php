import MagicCheckoutV2Routes from './MagicCheckoutRoutesV2';
import { isValidPlatform, VALID_PLATFORMS } from './helper';

describe('L0 Route Constants - Initial Merchant Onboarding Checks', () => {
  test('Should only render Setup and Settings route if platform is not valid/set', () => {
    const user = {
      isMagicCouponEngineEnabled: true,
      isMagicSettingsEnabled: true,
    };
    const abExp = {};
    let platform;
    MagicCheckoutV2Routes.forEach((route) => {
      if (route.tabName === 'Setup & Settings')
        expect(route.condition(user, abExp, platform)).toBe(true);
      else expect(route.condition(user, abExp, platform)).toBe(false);
    });
  });

  test('Should not render Setup and Settings route incase isMagicSettingsEnabled flag is not enabled even if valid platform is set', () => {
    const user = {
      isMagicCouponEngineEnabled: true,
      isMagicSettingsEnabled: false,
    };
    const abExp = {};
    const platform = 'shopify';
    MagicCheckoutV2Routes.forEach((route) => {
      if (route.tabName === 'Setup & Settings')
        expect(route.condition(user, abExp, platform)).toBe(false);
      else expect(route.condition(user, abExp, platform)).toBe(true);
    });
  });

  test('Should render all L0 routes if appropriate flags are enabled and valid platform is set', () => {
    const user = {
      isMagicCouponEngineEnabled: true,
      isMagicSettingsEnabled: true,
    };
    const abExp = {};
    const platform = 'shopify';
    MagicCheckoutV2Routes.forEach((route) => {
      expect(route.condition(user, abExp, platform)).toBe(true);
    });
  });
});

describe('Valid Platform Check', () => {
  const _user = {};
  const _abExp = {};
  test('Should return true in case of valid platforms', () => {
    VALID_PLATFORMS.forEach((validPlatform) => {
      expect(isValidPlatform(_user, _abExp, validPlatform)).toBe(true);
    });
  });
  test('Should return false in case of invalid platforms', () => {
    [null, '', 'testplatform'].forEach((invalidPlatform) => {
      expect(isValidPlatform(_user, _abExp, invalidPlatform)).toBe(false);
    });
  });
});
