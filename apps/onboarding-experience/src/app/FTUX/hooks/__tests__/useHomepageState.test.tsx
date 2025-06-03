import { renderHook } from 'apps/onboarding-experience/src/services/test/jest-utils';
import useHomepageState from '../useHomepageState';
import { useStore } from '@federated/apps/shell/commonStore';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { getLayoutByMerchantType } from '@FTUX/utils/homepage';
import { HOMEPAGE_ELEMENTS } from '@FTUX/types/homepage';
import {
  hasAcceptedAnyPaymentChannel,
  hasAddedWebsite,
} from '@OnboardingExperienceCommons/utils/merchant';
import { MerchantActivationStatusEnum } from '@OnboardingExperienceCommons/types/merchant';

jest.mock('@federated/apps/shell/commonStore');
jest.mock('@FTUX/context/MerchantContext');
jest.mock('@FTUX/utils/homepage');
jest.mock('@OnboardingExperienceCommons/utils/merchant');

describe('useHomepageState hook', () => {
  beforeEach(() => {
    jest.clearAllMocks();

    // Mock default return values
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: null,
      onboardingData: null,
    });

    (useStore as unknown as jest.Mock).mockImplementation((selector) => {
      const state = {
        session: {
          user: { id: 'user-1' },
        },
      };
      return selector(state);
    });

    (getLayoutByMerchantType as jest.Mock).mockReturnValue([
      HOMEPAGE_ELEMENTS.ACCORDION,
      HOMEPAGE_ELEMENTS.NOCODE_NUDGE,
    ]);

    (hasAcceptedAnyPaymentChannel as jest.Mock).mockReturnValue(false);
    (hasAddedWebsite as jest.Mock).mockReturnValue(false);
  });

  test('returns empty array when merchant data is not available', () => {
    // Execute the hook with null merchant data
    const { result } = renderHook(() => useHomepageState());

    // Verify that an empty array is returned
    expect(result.current).toEqual([]);
    expect(getLayoutByMerchantType).not.toHaveBeenCalled();
  });

  test('returns empty array when onboarding data is not available', () => {
    // Mock merchant data but not onboarding data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: { id: 'merchant-1' },
      },
      onboardingData: null,
    });

    // Execute the hook
    const { result } = renderHook(() => useHomepageState());

    // Verify that an empty array is returned
    expect(result.current).toEqual([]);
    expect(getLayoutByMerchantType).not.toHaveBeenCalled();
  });

  test('returns empty array when payment channels are not available', () => {
    // Mock merchant data without payment channels
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          id: 'merchant-1',
          business: {},
        },
      },
      onboardingData: {
        merchantOnboardingData: {},
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useHomepageState());

    // Verify that an empty array is returned
    expect(result.current).toEqual([]);
    expect(getLayoutByMerchantType).not.toHaveBeenCalled();
  });

  test('calls getLayoutByMerchantType with correct parameters for PG merchant', () => {
    const mockPaymentChannels = { website: { accept: true } };

    // Mock payment channel detection for PG merchant
    (hasAcceptedAnyPaymentChannel as jest.Mock).mockImplementation((channels, options) => {
      // Return true only for PG_CHANNEL_OPTIONS
      return options.includes('Websites');
    });

    // Mock merchant data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          id: 'merchant-1',
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
          activation: {
            status: MerchantActivationStatusEnum.ACTIVATED,
            isTransacted: false,
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: null,
          },
        },
      },
    });

    // Execute the hook
    renderHook(() => useHomepageState());

    // Verify getLayoutByMerchantType was called with correct parameters
    expect(getLayoutByMerchantType).toHaveBeenCalledWith({
      isPgMerchant: false,
      isNoCodeMerchant: false,
      hasWebsite: false,
    });
  });

  test('calls getLayoutByMerchantType with correct parameters for no-code merchant', () => {
    const mockPaymentChannels = { social: { accept: true } };

    // Mock payment channel detection for no-code merchant
    (hasAcceptedAnyPaymentChannel as jest.Mock).mockImplementation((channels, options) => {
      // Return true only for NO_CODE_CHANNEL_OPTIONS
      return options.includes('SocialMedia');
    });

    // Mock merchant data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          id: 'merchant-1',
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
          activation: {
            status: MerchantActivationStatusEnum.ACTIVATED,
            isTransacted: false,
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: null,
          },
        },
      },
    });

    // Execute the hook
    renderHook(() => useHomepageState());

    // Verify getLayoutByMerchantType was called with correct parameters
    expect(getLayoutByMerchantType).toHaveBeenCalledWith({
      isPgMerchant: false,
      isNoCodeMerchant: false,
      hasWebsite: false,
    });
  });

  test('considers website as added when hasAddedWebsite returns true', () => {
    const mockPaymentChannels = { website: { accept: true } };

    // Mock website detection
    (hasAddedWebsite as jest.Mock).mockReturnValue(true);

    // Mock merchant data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          id: 'merchant-1',
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
          activation: {
            status: MerchantActivationStatusEnum.ACTIVATED,
            isTransacted: false,
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: null,
          },
        },
      },
    });

    // Execute the hook
    renderHook(() => useHomepageState());

    // Verify getLayoutByMerchantType was called with hasWebsite=true
    expect(getLayoutByMerchantType).toHaveBeenCalledWith(
      expect.objectContaining({
        hasWebsite: true,
      }),
    );
  });

  test('considers website as added when websiteVerificationStatus is in progress', () => {
    const mockPaymentChannels = { website: { accept: true } };

    // Mock merchant data with website verification in progress
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          id: 'merchant-1',
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
          activation: {
            status: MerchantActivationStatusEnum.ACTIVATED,
            isTransacted: false,
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: {
              currentStatus: 'IN_PROGRESS',
              mainPageUrl: 'https://example.com',
            },
          },
        },
      },
    });

    // Execute the hook
    renderHook(() => useHomepageState());

    // Verify getLayoutByMerchantType was called with hasWebsite=true
    expect(getLayoutByMerchantType).toHaveBeenCalledWith(
      expect.objectContaining({
        hasWebsite: true,
      }),
    );
  });

  test('adds COMPLETED_TRANSACTION element when merchant has transacted', () => {
    const mockPaymentChannels = { website: { accept: true } };
    const mockLayout = [HOMEPAGE_ELEMENTS.ACCORDION, HOMEPAGE_ELEMENTS.NOCODE_NUDGE];

    // Mock getLayoutByMerchantType to return a known layout
    (getLayoutByMerchantType as jest.Mock).mockReturnValue([...mockLayout]);

    // Mock merchant data with completed transaction
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          id: 'merchant-1',
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
          activation: {
            status: MerchantActivationStatusEnum.ACTIVATED,
            isTransacted: true,
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: null,
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useHomepageState());

    // Verify COMPLETED_TRANSACTION was added at the beginning of the array
    expect(result.current[0]).toBe(HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION);
    expect(result.current.slice(1)).toEqual(mockLayout);
  });

  test('adds PREACTIVATION_BANNER element when merchant is not activated', () => {
    const mockPaymentChannels = { website: { accept: true } };
    const mockLayout = [HOMEPAGE_ELEMENTS.ACCORDION, HOMEPAGE_ELEMENTS.NOCODE_NUDGE];

    // Mock getLayoutByMerchantType to return a known layout
    (getLayoutByMerchantType as jest.Mock).mockReturnValue([...mockLayout]);

    // Mock merchant data with non-activated status
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          id: 'merchant-1',
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
          activation: {
            status: MerchantActivationStatusEnum.UNDER_REVIEW,
            isTransacted: false,
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: null,
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useHomepageState());

    // Verify PREACTIVATION_BANNER was added at the beginning of the array
    expect(result.current[0]).toBe(HOMEPAGE_ELEMENTS.PREACTIVATION_BANNER);
    expect(result.current.slice(1)).toEqual(mockLayout);
  });

  test('adds both PREACTIVATION_BANNER and COMPLETED_TRANSACTION when applicable', () => {
    const mockPaymentChannels = { website: { accept: true } };
    const mockLayout = [HOMEPAGE_ELEMENTS.ACCORDION, HOMEPAGE_ELEMENTS.NOCODE_NUDGE];

    // Mock getLayoutByMerchantType to return a known layout
    (getLayoutByMerchantType as jest.Mock).mockReturnValue([...mockLayout]);

    // Mock merchant data with non-activated status and completed transaction
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          id: 'merchant-1',
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
          activation: {
            status: MerchantActivationStatusEnum.UNDER_REVIEW,
            isTransacted: true,
          },
        },
      },
      onboardingData: {
        merchantOnboardingData: {
          websiteVerificationUpdateStatus: {
            verificationStatus: null,
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useHomepageState());

    // Verify both elements were added at the beginning (PREACTIVATION_BANNER first)
    expect(result.current[0]).toBe(HOMEPAGE_ELEMENTS.PREACTIVATION_BANNER);
    expect(result.current[1]).toBe(HOMEPAGE_ELEMENTS.COMPLETED_TRANSACTION);
    expect(result.current.slice(2)).toEqual(mockLayout);
  });
});
