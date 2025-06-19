import React from 'react';
import { act } from '@testing-library/react';
import PlotlineMilestoneWidget from '../PlotlineMilestoneWidget';
import { useStore } from '@apps/shell/src/client/store/commonStore';
import { useSplitzService } from '@libs/web-nexus/common/splitz';
import { render, screen } from 'test-utils';

// Mock dependencies
jest.mock('@apps/shell/src/client/store/commonStore');
jest.mock('@libs/web-nexus/common/splitz');
jest.mock('@dashboards/payments/utils/merchantFetch', () => ({
  merchantFetch: jest.fn(),
}));

// Declare plotline type
declare global {
  interface Window {
    plotline?: jest.Mock;
  }
}

const createMockData = ({
  merchantId = '123',
  daysSinceActivation = 15,
  mode = 'live',
  isExperimentActive = true,
  isMIDWhitelisted = false,
  transactionCount = 2,
  hasMerchant = true,
  businessType = '1', // not unregistered
  apiError = false,
  isLoading = false,
}) => {
  const mockStoreValue = {
    session: {
      user: {
        business_type: businessType,
        merchant: hasMerchant
          ? {
              id: merchantId,
              activated_at: Math.floor(
                (Date.now() - daysSinceActivation * 24 * 60 * 60 * 1000) / 1000,
              ),
            }
          : null,
      },
      mode: mode,
    },
  };

  const mockSplitzValue = {
    abExperiments: {
      plotline_milestone_widget: { variables: { result: isExperimentActive ? 'on' : 'off' } },
      plotline_milestone_whitelisted_mids: {
        variables: { result: isMIDWhitelisted ? 'on' : 'off' },
      },
    },
  };

  return {
    store: {
      value: mockStoreValue,
      mock: (mockUseStore: jest.Mock) => {
        mockUseStore.mockImplementation((selector) => selector(mockStoreValue));
      },
    },
    splitz: {
      value: mockSplitzValue,
      mock: (mockUseSplitzService: jest.Mock) => {
        mockUseSplitzService.mockReturnValue(mockSplitzValue);
      },
    },
    merchantFetch: {
      mock: (mockMerchantFetch: jest.Mock) => {
        if (isLoading) {
          mockMerchantFetch.mockImplementation(() => new Promise(() => undefined));
        } else if (apiError) {
          mockMerchantFetch.mockRejectedValue(new Error('API Error'));
        } else {
          mockMerchantFetch.mockResolvedValue({ data: { count: transactionCount } });
        }
      },
    },
  };
};

describe('PlotlineMilestoneWidget', () => {
  const mockUseStore = useStore as unknown as jest.Mock;
  const mockUseSplitzService = useSplitzService as unknown as jest.Mock;
  const mockMerchantFetch = require('@dashboards/payments/utils/merchantFetch').merchantFetch;

  beforeEach(() => {
    jest.clearAllMocks();
    window.plotline = jest.fn();
  });

  // Case 1
  it('should show the Plotline widget for a whitelisted MID within the activation period', async () => {
    const mocks = createMockData({
      isMIDWhitelisted: true,
      daysSinceActivation: 15,
    });

    mocks.store.mock(mockUseStore);
    mocks.splitz.mock(mockUseSplitzService);
    mocks.merchantFetch.mock(mockMerchantFetch);

    await act(async () => {
      render(<PlotlineMilestoneWidget />);
    });

    expect(window.plotline).toHaveBeenCalled();
  });

  // Case 2
  it('should show nothing for a whitelisted MID outside the activation period with no transactions', async () => {
    const mocks = createMockData({
      isMIDWhitelisted: true,
      daysSinceActivation: 31,
      transactionCount: 0,
    });

    mocks.store.mock(mockUseStore);
    mocks.splitz.mock(mockUseSplitzService);
    mocks.merchantFetch.mock(mockMerchantFetch);

    await act(async () => {
      render(<PlotlineMilestoneWidget />);
    });

    expect(window.plotline).not.toHaveBeenCalled();
  });

  // Case 3
  it('should show the frontend coded banner for a whitelisted MID outside activation period with transactions', async () => {
    const mocks = createMockData({
      isMIDWhitelisted: true,
      daysSinceActivation: 31,
      transactionCount: 1,
    });
    mocks.store.mock(mockUseStore);
    mocks.splitz.mock(mockUseSplitzService);
    mocks.merchantFetch.mock(mockMerchantFetch);

    await act(async () => {
      render(<PlotlineMilestoneWidget />);
    });

    expect(window.plotline).not.toHaveBeenCalled();
  });

  // Case 4
  it('should show the widget for a non-whitelisted merchant in FTUX state and in experiment', async () => {
    const mocks = createMockData({
      isMIDWhitelisted: false,
      daysSinceActivation: 15,
      transactionCount: 5,
      isExperimentActive: true,
    });

    mocks.store.mock(mockUseStore);
    mocks.splitz.mock(mockUseSplitzService);
    mocks.merchantFetch.mock(mockMerchantFetch);

    await act(async () => {
      render(<PlotlineMilestoneWidget />);
    });

    expect(window.plotline).toHaveBeenCalled();
  });

  // Case 5
  it('should show nothing for an FTUX merchant not in the experiment', async () => {
    const mocks = createMockData({
      isMIDWhitelisted: false,
      daysSinceActivation: 15,
      transactionCount: 5,
      isExperimentActive: false,
    });

    mocks.store.mock(mockUseStore);
    mocks.splitz.mock(mockUseSplitzService);
    mocks.merchantFetch.mock(mockMerchantFetch);

    await act(async () => {
      render(<PlotlineMilestoneWidget />);
    });

    expect(window.plotline).not.toHaveBeenCalled();
  });

  // Case 6
  it('should show nothing for a merchant with too many transactions to be in FTUX', async () => {
    const mocks = createMockData({
      isMIDWhitelisted: false,
      daysSinceActivation: 15,
      transactionCount: 6,
      isExperimentActive: true,
    });

    mocks.store.mock(mockUseStore);
    mocks.splitz.mock(mockUseSplitzService);
    mocks.merchantFetch.mock(mockMerchantFetch);

    await act(async () => {
      render(<PlotlineMilestoneWidget />);
    });

    expect(window.plotline).not.toHaveBeenCalled();
  });

  // Case 7
  it('should show nothing for a non-whitelisted merchant outside activation window', async () => {
    const mocks = createMockData({
      isMIDWhitelisted: false,
      daysSinceActivation: 31,
      isExperimentActive: true,
    });

    mocks.store.mock(mockUseStore);
    mocks.splitz.mock(mockUseSplitzService);
    mocks.merchantFetch.mock(mockMerchantFetch);

    await act(async () => {
      render(<PlotlineMilestoneWidget />);
    });

    expect(window.plotline).not.toHaveBeenCalled();
  });
});
