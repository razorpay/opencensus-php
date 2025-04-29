import { renderHook } from 'apps/onboarding-experience/src/services/test/jest-utils';
import useHomepageState from '../useHomepageState';
import { PG_CHANNEL_OPTIONS, NO_CODE_CHANNEL_OPTIONS } from '@FTUX/constants/homepage';
import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import {
  areAnyOptionsAccepted,
  hasAddedWebsite,
} from 'apps/onboarding-experience/src/common/utils/merchant';
import { getLayoutByMerchantType } from '@FTUX/utils/homepage';
import useMerchant from 'apps/onboarding-experience/src/common/hooks/useMerchant';
import useMerchantOnboardingData from 'apps/onboarding-experience/src/common/hooks/useMerchantOnboardingData';
import { useStore } from '@federated/apps/shell/commonStore';

// Mock dependencies
jest.mock('apps/onboarding-experience/src/common/utils/merchant');
jest.mock('@FTUX/utils/homepage');
jest.mock('apps/onboarding-experience/src/common/hooks/useMerchant');
jest.mock('apps/onboarding-experience/src/common/hooks/useMerchantOnboardingData');
jest.mock('@federated/apps/shell/commonStore');

describe('useHomepageState hook', () => {
  beforeEach(() => {
    jest.clearAllMocks();

    // Default mock return values
    (useMerchant as jest.Mock).mockReturnValue({ data: null });
    (useMerchantOnboardingData as jest.Mock).mockReturnValue({ data: null });
    (areAnyOptionsAccepted as jest.Mock).mockReturnValue(false);
    (hasAddedWebsite as jest.Mock).mockReturnValue(false);
    (getLayoutByMerchantType as jest.Mock).mockReturnValue([]);
    (useStore as unknown as jest.Mock).mockReturnValue({
      session: { user: { id: 'test-user-id', websites: [] } },
    });
  });

  test('returns empty array when merchantData is not available', () => {
    (useMerchant as jest.Mock).mockReturnValue({ data: null });
    (useMerchantOnboardingData as jest.Mock).mockReturnValue({
      data: { merchantOnboardingData: {} },
    });

    const { result } = renderHook(() => useHomepageState());
    expect(result.current).toEqual([]);
  });

  test('returns empty array when ftuxData is not available', () => {
    (useMerchant as jest.Mock).mockReturnValue({
      data: {
        merchantById: { business: { paymentAcceptanceChannels: [] } },
      },
    });
    (useMerchantOnboardingData as jest.Mock).mockReturnValue({ data: null });

    const { result } = renderHook(() => useHomepageState());
    expect(result.current).toEqual([]);
  });

  test('returns empty array when paymentChannels is not available', () => {
    (useMerchant as jest.Mock).mockReturnValue({
      data: {
        merchantById: { business: {} },
      },
    });
    (useMerchantOnboardingData as jest.Mock).mockReturnValue({
      data: {
        merchantOnboardingData: {},
      },
    });

    const { result } = renderHook(() => useHomepageState());
    expect(result.current).toEqual([]);
  });

  test('returns PG layout when isPgMerchant is true', () => {
    // Mock data for PG merchant
    (useMerchant as jest.Mock).mockReturnValue({
      data: {
        merchantById: {
          business: {
            paymentAcceptanceChannels: ['some_pg_channel'],
          },
          activation: {
            isTransacted: false,
          },
        },
      },
    });
    (useMerchantOnboardingData as jest.Mock).mockReturnValue({
      data: {
        merchantOnboardingData: {},
      },
    });

    // Mock PG merchant check
    (areAnyOptionsAccepted as jest.Mock).mockImplementation((channels, options) => {
      if (options === PG_CHANNEL_OPTIONS) return true;
      return false;
    });

    // Mock layout return value
    const pgLayout = [
      HOMEPAGE_ELEMENTS.ACCORDION,
      HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT,
      HOMEPAGE_ELEMENTS.PAYMENT_HANDLE,
      HOMEPAGE_ELEMENTS.BROWSE_ALL,
    ];
    (getLayoutByMerchantType as jest.Mock).mockReturnValue([...pgLayout]);

    const { result } = renderHook(() => useHomepageState());

    // Verify correct params were passed to getLayoutByMerchantType
    expect(getLayoutByMerchantType).toHaveBeenCalledWith({
      isPgMerchant: true,
      isNoCodeMerchant: false,
      hasWebsite: false,
    });

    // Verify the correct layout is returned
    expect(result.current).toEqual(pgLayout);
  });

  test('adds transaction banner when merchant has transacted', () => {
    // Mock data for PG merchant that has transacted
    (useMerchant as jest.Mock).mockReturnValue({
      data: {
        merchantById: {
          business: {
            paymentAcceptanceChannels: ['some_pg_channel'],
          },
          activation: {
            isTransacted: true,
          },
        },
      },
    });
    (useMerchantOnboardingData as jest.Mock).mockReturnValue({
      data: {
        merchantOnboardingData: {},
      },
    });

    // Mock PG merchant check
    (areAnyOptionsAccepted as jest.Mock).mockImplementation((channels, options) => {
      if (options === PG_CHANNEL_OPTIONS) return true;
      return false;
    });

    // Mock layout return value
    const pgLayout = [HOMEPAGE_ELEMENTS.ACCORDION, HOMEPAGE_ELEMENTS.WAYS_FOR_PAYMENT];
    (getLayoutByMerchantType as jest.Mock).mockReturnValue([...pgLayout]);

    const { result } = renderHook(() => useHomepageState());

    // Verify transaction banner is added at the beginning of the layout
    expect(result.current[0]).toBe(HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION);
    expect(result.current.slice(1)).toEqual(pgLayout);
  });

  test('considers website verification status when determining layout', () => {
    // Mock data for merchant with website verification in progress
    (useMerchant as jest.Mock).mockReturnValue({
      data: {
        merchantById: {
          business: {
            paymentAcceptanceChannels: ['some_channel'],
          },
          activation: {
            isTransacted: false,
          },
        },
      },
    });
    (useMerchantOnboardingData as jest.Mock).mockReturnValue({
      data: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: {
              currentStatus: 'in_progress',
              mainPageUrl: 'https://example.com',
            },
          },
        },
      },
    });

    // Mock merchant checks
    (areAnyOptionsAccepted as jest.Mock).mockReturnValue(false);
    (hasAddedWebsite as jest.Mock).mockReturnValue(false);

    // Mock combined layout for merchants with website
    const combinedLayout = [
      HOMEPAGE_ELEMENTS.ACCORDION,
      HOMEPAGE_ELEMENTS.NOCODE_NUDGE,
      HOMEPAGE_ELEMENTS.BROWSE_ALL,
    ];
    (getLayoutByMerchantType as jest.Mock).mockReturnValue([...combinedLayout]);

    const { result } = renderHook(() => useHomepageState());

    // Verify website status is considered (isWebsiteAddInProgress should be true)
    expect(getLayoutByMerchantType).toHaveBeenCalledWith({
      isPgMerchant: false,
      isNoCodeMerchant: false,
      hasWebsite: true,
    });

    // Verify the correct layout is returned
    expect(result.current).toEqual(combinedLayout);
  });
});
