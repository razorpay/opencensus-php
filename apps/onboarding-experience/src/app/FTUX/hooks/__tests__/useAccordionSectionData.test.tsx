import { renderHook } from 'apps/onboarding-experience/src/services/test/jest-utils';
import useAccordionSectionData from '../useAccordionSectionData';
import { useStore } from '@federated/apps/shell/commonStore';
import { PAYMENT_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/types/merchant';
import { getAccordionWebsiteTitle, getAccordionCompletedSteps } from '@FTUX/utils/homepage';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { hasAcceptedAnyPaymentChannel } from '@OnboardingExperienceCommons/utils/merchant';

jest.mock('@OnboardingExperienceCommons/hooks/useMerchant');
jest.mock('@federated/apps/shell/commonStore');
jest.mock('@FTUX/utils/homepage');
jest.mock('@FTUX/context/MerchantContext');
jest.mock('@OnboardingExperienceCommons/utils/merchant');

describe('useAccordionSectionData hook', () => {
  beforeEach(() => {
    jest.clearAllMocks();

    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: null,
    });
    (useStore as unknown as jest.Mock).mockImplementation((selector) => {
      const state = {
        session: {
          mode: 'test',
        },
      };
      return selector(state);
    });
    (getAccordionWebsiteTitle as jest.Mock).mockReturnValue('Add your website details');
    (getAccordionCompletedSteps as jest.Mock).mockReturnValue(0);
    (hasAcceptedAnyPaymentChannel as jest.Mock).mockReturnValue(false);
  });

  test('returns correct expanded step when no steps are completed', () => {
    // Mock getAccordionCompletedSteps to return 0
    (getAccordionCompletedSteps as jest.Mock).mockReturnValue(0);

    // Mock merchant data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: {},
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify the correct step values are returned
    expect(result.current.expandedStep).toBe(0);
    expect(result.current.completedSteps).toBe(0);
    expect(result.current.accordionData.length).toBe(3);
    expect(getAccordionCompletedSteps).toHaveBeenCalled();
  });

  test('returns correct expanded step when website is added but no API keys', () => {
    // Mock getAccordionCompletedSteps to return 1
    (getAccordionCompletedSteps as jest.Mock).mockReturnValue(1);

    // Mock merchant data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: {},
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify the expanded step is 1
    expect(result.current.expandedStep).toBe(1);
    expect(result.current.completedSteps).toBe(1);
    expect(getAccordionCompletedSteps).toHaveBeenCalled();
  });

  test('returns correct expanded step when website and API keys exist but no transactions', () => {
    // Mock getAccordionCompletedSteps to return 2
    (getAccordionCompletedSteps as jest.Mock).mockReturnValue(2);

    // Mock merchant data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [{ id: 'api-key-1', createdAt: '2023-01-01' }],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: {},
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify the expanded step is 2
    expect(result.current.expandedStep).toBe(2);
    expect(result.current.completedSteps).toBe(2);
    expect(getAccordionCompletedSteps).toHaveBeenCalled();
  });

  test('returns expanded step 3 when all steps are completed', () => {
    // Mock getAccordionCompletedSteps to return 3
    (getAccordionCompletedSteps as jest.Mock).mockReturnValue(3);

    // Mock merchant data
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [{ id: 'api-key-1', createdAt: '2023-01-01' }],
          activation: {
            isActivated: true,
            isTransacted: true,
          },
          business: {
            paymentAcceptanceChannels: {},
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify the expanded step is 3
    expect(result.current.expandedStep).toBe(3);
    expect(result.current.completedSteps).toBe(3);
    expect(getAccordionCompletedSteps).toHaveBeenCalled();
  });

  test('calls getAccordionWebsiteTitle with payment channels', () => {
    const mockPaymentChannels = {
      [PAYMENT_CHANNEL_OPTIONS.Websites]: { accept: true, urls: [] },
      [PAYMENT_CHANNEL_OPTIONS.Android]: { accept: false, urls: [] },
    };

    // Mock merchant data with payment channels
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
        },
      },
    });

    // Execute the hook
    renderHook(() => useAccordionSectionData());

    // Verify getAccordionWebsiteTitle was called with payment channels
    expect(getAccordionWebsiteTitle).toHaveBeenCalledWith(mockPaymentChannels);
    expect(getAccordionCompletedSteps).toHaveBeenCalled();
  });

  test('returns test mode badge for payment gateway section when in test mode', () => {
    // Mock session with test mode
    (useStore as unknown as jest.Mock).mockImplementation((selector) => {
      const state = {
        session: {
          mode: 'test',
        },
      };
      return selector(state);
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Get the payment gateway section (index 1)
    const paymentGatewaySection = result.current.accordionData[1];
    const titleSuffix = paymentGatewaySection.getTitleSuffix?.(true);

    // Expect a non-null badge for test mode
    expect(titleSuffix).not.toBeNull();
  });

  test('returns live mode badge for payment gateway section when in live mode', () => {
    // Mock session with live mode
    (useStore as unknown as jest.Mock).mockImplementation((selector) => {
      const state = {
        session: {
          mode: 'live',
        },
      };
      return selector(state);
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Get the payment gateway section (index 1)
    const paymentGatewaySection = result.current.accordionData[1];
    const titleSuffix = paymentGatewaySection.getTitleSuffix?.(true);

    // Expect a non-null badge for live mode
    expect(titleSuffix).not.toBeNull();
  });

  test('returns pending badge for website section when expanded step is 0', () => {
    // Mock getAccordionCompletedSteps to return 0
    (getAccordionCompletedSteps as jest.Mock).mockReturnValue(0);

    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: {},
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Get the website section (index 0)
    const websiteSection = result.current.accordionData[0];
    const titleSuffix = websiteSection.getTitleSuffix?.(false);

    // Expect a pending badge for the website section
    expect(titleSuffix).not.toBeNull();
  });

  test('does not return badge for collapsed sections that are not the expanded step', () => {
    // Mock getAccordionCompletedSteps to return 1
    (getAccordionCompletedSteps as jest.Mock).mockReturnValue(1);

    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: {},
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Get the website section (index 0)
    const websiteSection = result.current.accordionData[0];
    const titleSuffix = websiteSection.getTitleSuffix?.(false);

    // Expect no badge for the website section when not active
    expect(titleSuffix).toBeNull();
  });

  test('handles pre-activation case - skips website section and shows only 2 accordion items', () => {
    // Mock non-activated merchant
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: false,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: {},
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify only 2 accordion items are shown (payment gateway and transactions)
    expect(result.current.accordionData.length).toBe(2);

    // Verify the first item is the payment gateway section
    expect(result.current.accordionData[0].title).toBe('Set up your payment gateway');

    // Verify the second item is the transactions section
    expect(result.current.accordionData[1].title).toBe('Accept your first payment');
  });

  test('handles pre-activation case with API keys', () => {
    // Mock getAccordionCompletedSteps to return 1 for a non-activated merchant with API keys
    (getAccordionCompletedSteps as jest.Mock).mockReturnValue(1);

    // Mock non-activated merchant with API keys
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [{ id: 'api-key-1', createdAt: '2023-01-01' }],
          activation: {
            isActivated: false,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: {},
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify the expanded step is 1
    expect(result.current.expandedStep).toBe(1);
    expect(result.current.completedSteps).toBe(1);
    expect(result.current.accordionData.length).toBe(2);
  });

  test('handles pre-activation case with API keys and transactions', () => {
    // Mock getAccordionCompletedSteps to return 2 for a non-activated merchant with transactions
    (getAccordionCompletedSteps as jest.Mock).mockReturnValue(2);

    // Mock non-activated merchant with API keys and transactions
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [{ id: 'api-key-1', createdAt: '2023-01-01' }],
          activation: {
            isActivated: false,
            isTransacted: true,
          },
          business: {
            paymentAcceptanceChannels: {},
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify the expanded step is 2
    expect(result.current.expandedStep).toBe(2);
    expect(result.current.completedSteps).toBe(2);
    expect(result.current.accordionData.length).toBe(2);
  });

  test('correctly sets isAppOnlyMerchant flag when merchant has only mobile apps', () => {
    // Mock merchant data with only mobile app channels
    (hasAcceptedAnyPaymentChannel as jest.Mock).mockImplementation((channels, options) => {
      if (options.includes(PAYMENT_CHANNEL_OPTIONS.Websites)) {
        return false; // Not a website merchant
      }
      if (
        options.includes(PAYMENT_CHANNEL_OPTIONS.Android) ||
        options.includes(PAYMENT_CHANNEL_OPTIONS.IOS)
      ) {
        return true; // Is an app merchant
      }
      return false;
    });

    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: {
              [PAYMENT_CHANNEL_OPTIONS.Android]: { accept: true },
              [PAYMENT_CHANNEL_OPTIONS.Websites]: { accept: false },
            },
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify isAppOnlyMerchant is true
    expect(result.current.isAppOnlyMerchant).toBe(true);
  });

  test('correctly sets isAppOnlyMerchant flag to false when merchant has website', () => {
    // Mock merchant data with website channel
    (hasAcceptedAnyPaymentChannel as jest.Mock).mockImplementation((channels, options) => {
      if (options.includes(PAYMENT_CHANNEL_OPTIONS.Websites)) {
        return true; // Is a website merchant
      }
      if (
        options.includes(PAYMENT_CHANNEL_OPTIONS.Android) ||
        options.includes(PAYMENT_CHANNEL_OPTIONS.IOS)
      ) {
        return true; // Is also an app merchant
      }
      return false;
    });

    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: {
              [PAYMENT_CHANNEL_OPTIONS.Android]: { accept: true },
              [PAYMENT_CHANNEL_OPTIONS.Websites]: { accept: true },
            },
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify isAppOnlyMerchant is false when merchant has both website and app
    expect(result.current.isAppOnlyMerchant).toBe(false);
  });

  test('returns isAppOnlyMerchant as true when merchant accepts only Android/iOS channels', () => {
    // Mock payment channels with only mobile apps
    const mockPaymentChannels = {
      [PAYMENT_CHANNEL_OPTIONS.Android]: { accept: true, urls: [] },
      [PAYMENT_CHANNEL_OPTIONS.IOS]: { accept: true, urls: [] },
      [PAYMENT_CHANNEL_OPTIONS.Websites]: { accept: false, urls: [] },
    };

    // Setup hasAcceptedAnyPaymentChannel mock to return true for Android/iOS and false for Websites
    (hasAcceptedAnyPaymentChannel as jest.Mock).mockImplementation((channels, options) => {
      if (
        options.includes(PAYMENT_CHANNEL_OPTIONS.Android) ||
        options.includes(PAYMENT_CHANNEL_OPTIONS.IOS)
      ) {
        return true;
      }
      if (options.includes(PAYMENT_CHANNEL_OPTIONS.Websites)) {
        return false;
      }
      return false;
    });

    // Mock merchant data with payment channels
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify isAppOnlyMerchant is true
    expect(result.current.isAppOnlyMerchant).toBe(true);
  });

  test('returns isAppOnlyMerchant as false when merchant accepts website channel', () => {
    // Mock payment channels with website
    const mockPaymentChannels = {
      [PAYMENT_CHANNEL_OPTIONS.Android]: { accept: false, urls: [] },
      [PAYMENT_CHANNEL_OPTIONS.IOS]: { accept: false, urls: [] },
      [PAYMENT_CHANNEL_OPTIONS.Websites]: { accept: true, urls: [] },
    };

    // Setup hasAcceptedAnyPaymentChannel mock to return correct values for different channels
    (hasAcceptedAnyPaymentChannel as jest.Mock).mockImplementation((channels, options) => {
      if (options.includes(PAYMENT_CHANNEL_OPTIONS.Websites)) {
        return true;
      }
      if (
        options.includes(PAYMENT_CHANNEL_OPTIONS.Android) ||
        options.includes(PAYMENT_CHANNEL_OPTIONS.IOS)
      ) {
        return false;
      }
      return false;
    });

    // Mock merchant data with payment channels
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify isAppOnlyMerchant is false
    expect(result.current.isAppOnlyMerchant).toBe(false);
  });

  test('returns isAppOnlyMerchant as false when merchant accepts both website and mobile channels', () => {
    // Mock payment channels with both website and mobile
    const mockPaymentChannels = {
      [PAYMENT_CHANNEL_OPTIONS.Android]: { accept: true, urls: [] },
      [PAYMENT_CHANNEL_OPTIONS.IOS]: { accept: true, urls: [] },
      [PAYMENT_CHANNEL_OPTIONS.Websites]: { accept: true, urls: [] },
    };

    // Setup hasAcceptedAnyPaymentChannel mock to return true for all channel types
    (hasAcceptedAnyPaymentChannel as jest.Mock).mockImplementation((channels, options) => {
      return true;
    });

    // Mock merchant data with payment channels
    (useMerchantContext as jest.Mock).mockReturnValue({
      merchantData: {
        merchantById: {
          apiKeys: [],
          activation: {
            isActivated: true,
            isTransacted: false,
          },
          business: {
            paymentAcceptanceChannels: mockPaymentChannels,
          },
        },
      },
    });

    // Execute the hook
    const { result } = renderHook(() => useAccordionSectionData());

    // Verify isAppOnlyMerchant is false when both website and mobile are accepted
    expect(result.current.isAppOnlyMerchant).toBe(false);
  });
});
