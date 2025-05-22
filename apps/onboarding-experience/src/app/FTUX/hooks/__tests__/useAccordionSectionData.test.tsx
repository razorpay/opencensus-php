import { renderHook } from 'apps/onboarding-experience/src/services/test/jest-utils';
import useAccordionSectionData from '../useAccordionSectionData';
import { useStore } from '@federated/apps/shell/commonStore';
import { PAYMENT_CHANNEL_OPTIONS } from '@OnboardingExperienceCommons/types/merchant';
import { getAccordionWebsiteTitle } from '@FTUX/utils/homepage';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { hasAddedWebsite } from '@OnboardingExperienceCommons/utils/merchant';

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
    (hasAddedWebsite as jest.Mock).mockReturnValue(false);
  });

  test('returns correct active step when no website, API keys, or transactions', () => {
    // Mock hasAddedWebsite to return false
    (hasAddedWebsite as jest.Mock).mockReturnValue(false);

    // Mock merchant data with no website, API keys, or transactions
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

    // Verify the correct active step is returned
    expect(result.current.activeStep).toBe(0);
    expect(result.current.accordionData.length).toBe(3);
    expect(hasAddedWebsite).toHaveBeenCalledWith({});
  });

  test('returns correct active step when website exists but no API keys', () => {
    // Mock hasAddedWebsite to return true
    (hasAddedWebsite as jest.Mock).mockReturnValue(true);

    // Mock merchant data with no API keys or transactions
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

    // Verify the active step is 1 (next step after website)
    expect(result.current.activeStep).toBe(1);
    expect(hasAddedWebsite).toHaveBeenCalledWith({});
  });

  test('returns correct active step when website and API keys exist but no transactions', () => {
    // Mock hasAddedWebsite to return true
    (hasAddedWebsite as jest.Mock).mockReturnValue(true);

    // Mock merchant data with API keys but no transactions
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

    // Verify the active step is 2 (transactions step)
    expect(result.current.activeStep).toBe(2);
    expect(hasAddedWebsite).toHaveBeenCalledWith({});
  });

  test('returns active step 3 when all steps are completed', () => {
    // Mock hasAddedWebsite to return true
    (hasAddedWebsite as jest.Mock).mockReturnValue(true);

    // Mock merchant data with API keys and completed transactions
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

    // Verify the active step is 3 (all steps completed)
    expect(result.current.activeStep).toBe(3);
    expect(hasAddedWebsite).toHaveBeenCalledWith({});
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
    expect(hasAddedWebsite).toHaveBeenCalledWith(mockPaymentChannels);
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

  test('returns pending badge for website section when active step is 0', () => {
    // Set active step to 0 (website step) by mocking hasAddedWebsite
    (hasAddedWebsite as jest.Mock).mockReturnValue(false);

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

  test('does not return badge for collapsed sections that are not the active step', () => {
    // Set active step to 1 (API keys step) by mocking hasAddedWebsite
    (hasAddedWebsite as jest.Mock).mockReturnValue(true);

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

    // Verify the active step is 0 (first item - payment gateway)
    expect(result.current.activeStep).toBe(0);
  });

  test('handles pre-activation case with API keys - active step should be 1', () => {
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

    // Verify the active step is 1 (transactions section)
    expect(result.current.activeStep).toBe(1);
    expect(result.current.accordionData.length).toBe(2);
  });

  test('handles pre-activation case with API keys and transactions - active step should be 2', () => {
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

    // Verify the active step is 2 (all steps completed)
    expect(result.current.activeStep).toBe(2);
    expect(result.current.accordionData.length).toBe(2);
  });
});
