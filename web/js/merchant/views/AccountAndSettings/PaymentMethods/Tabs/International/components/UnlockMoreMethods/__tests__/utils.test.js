import {
  isInstantBankTransferActivated,
  isMoneySaverAccountsActivated,
  getDefaultTab,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/UnlockMoreMethods/utils';
import { HUF, PRIVATE } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

import {
  isMoneySaverAccountsActivatedTests,
  isInstantBankTransferActivatedTests,
} from './mocks/fixtures';

// test suite
describe('Tests for isInstantBankTransferActivated - UnlockMoreMethods', () => {
  test.each(isInstantBankTransferActivatedTests)(
    'should return %p when leafInstrument is %p',
    (leafInstrument, expected) => {
      const result = isInstantBankTransferActivated(leafInstrument);
      expect(result).toBe(expected);
    },
  );
});

describe('Tests for isMoneySaverAccountsActivated - UnlockMoreMethods', () => {
  test.each(isMoneySaverAccountsActivatedTests)(
    'should return %p when b2bExportsAccounts is %p',
    (b2bExportsAccounts, expected) => {
      const result = isMoneySaverAccountsActivated(b2bExportsAccounts);
      expect(result).toBe(expected);
    },
  );
});

describe('Tests for getDefaultTab - UnlockMoreMethods', () => {
  test.each([
    [PRIVATE, 0],
    [HUF, 2],
    [undefined, 2],
  ])('Should return 0 if business type requires document collection', (businessType, tab) => {
    const defaultTab = getDefaultTab(businessType);
    expect(defaultTab).toBe(tab);
  });
});
