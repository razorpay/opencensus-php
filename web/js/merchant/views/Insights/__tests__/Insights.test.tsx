import React from 'react';
import { embedDashboard } from '@superset-ui/embedded-sdk';
import { userEvent, render, screen, waitFor } from 'test-utils';
import Insights from 'merchant/views/Insights/Insights';
import { DOCUMENTATION_ROUTES, SUCCESS_RATE_TABS } from 'merchant/views/Insights/constants';
import { useSupersetDashboard } from 'merchant/views/Insights/hooks/useSupersetDashboard';
import { getInsightsDataWithFlags } from 'merchant/views/Insights/utils/insightsDataManager';

jest.mock('@superset-ui/embedded-sdk', () => ({
  embedDashboard: jest.fn(),
}));

jest.mock('../hooks/useSupersetDashboard', () => ({
  useSupersetDashboard: jest.fn(),
}));

jest.mock('../utils/insightsDataManager', () => ({
  getInsightsDataWithFlags: jest.fn(),
}));

jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({
    activetab: 'overview',
    insights_dashboard: 'success-rate',
  }),
}));

jest.mock('../components/FilteredSection', () => ({
  __esModule: true,
  default: ({ selectedDateCallback }) => (
    <div>
      <div
        data-testid="date-range-picker-input"
        onClick={() => {
          const now = new Date();
          const nextDay = new Date(now);
          nextDay.setDate(now.getDate() + 1);
          nextDay.setHours(0, 0, 0, 0);

          const sevenDaysAgo = new Date(now);
          sevenDaysAgo.setDate(now.getDate() - 7);

          selectedDateCallback({
            from: Math.floor(sevenDaysAgo.getTime() / 1000),
            to: Math.floor(nextDay.getTime() / 1000),
          });
        }}
      >
        Select Date Range
      </div>
      <button>Refresh</button>
    </div>
  ),
}));

jest.mock('../components/HeaderSection', () => ({
  __esModule: true,
  default: () => (
    <div>
      <h1>Header Section</h1>
      <a
        href="https://razorpay.com/docs/payments/insights"
        target="_blank"
        rel="noopener noreferrer"
      >
        Documentation
      </a>
    </div>
  ),
}));

describe('Testing Insights Component', () => {
  const mockRefetch = jest.fn().mockResolvedValue({
    data: 'mockToken',
    isLoading: false,
    isError: false,
    error: null,
  });

  beforeEach(() => {
    (useSupersetDashboard as jest.Mock).mockReturnValue({
      refetch: mockRefetch,
      data: 'mockToken',
      isLoading: false,
      isError: false,
      error: null,
    });

    (getInsightsDataWithFlags as jest.Mock).mockReturnValue({
      insightsData: {
        checkout: [{ MagicX: 'test' }],
        'success-rate': [{ overview: '700' }],
      },
      hasMagicX: true,
      hasCheckoutData: true,
      hasSuccessRateData: true,
      hasApiData: true,
    });
  });

  const renderApp = () => {
    return render(<Insights />);
  };

  it('should render the component and removes the progressbar after some time', () => {
    const mockReturnValue = {
      refetch: mockRefetch,
      data: 'mockToken',
      isLoading: true,
      isError: false,
      error: null,
    };

    (useSupersetDashboard as jest.Mock).mockReturnValue(mockReturnValue);

    const { rerender } = renderApp();

    const progressBar = screen.getByRole('progressbar');
    expect(progressBar).toBeInTheDocument();

    (useSupersetDashboard as jest.Mock).mockReturnValue({ ...mockReturnValue, isLoading: false });

    rerender(<Insights />);

    expect(progressBar).not.toBeInTheDocument();
  });

  it('should render documentation link correctly', () => {
    renderApp();
    const tabName = SUCCESS_RATE_TABS[0].name;
    const documentationButton = screen.getByText('Documentation');
    expect(documentationButton).toBeInTheDocument();
    userEvent.click(documentationButton);

    const link = documentationButton.closest('a');
    expect(link).toHaveAttribute('href', DOCUMENTATION_ROUTES[tabName]);
    expect(link).toHaveAttribute('target', '_blank');
    expect(link).toHaveAttribute('rel', 'noopener noreferrer');
  });

  it('should pass date range with end date at 00:00:00 of next day to embedDashboard', async () => {
    renderApp();

    const dateRangePicker = screen.getByTestId('date-range-picker-input');
    userEvent.click(dateRangePicker);

    await waitFor(() => {
      expect(embedDashboard).toHaveBeenCalled();

      const callArgs = (embedDashboard as jest.Mock).mock.calls[0][0];
      const toTs = callArgs.dashboardUiConfig.urlParams.to_ts;

      const endDate = new Date(toTs * 1000);
      expect(endDate.getHours()).toBe(0);
      expect(endDate.getMinutes()).toBe(0);
      expect(endDate.getSeconds()).toBe(0);
    });
  });

  it('should use default timestamps when no date is selected', async () => {
    renderApp();

    await waitFor(() => {
      expect(embedDashboard).toHaveBeenCalledWith(
        expect.objectContaining({
          dashboardUiConfig: expect.objectContaining({
            urlParams: expect.objectContaining({
              from_ts: expect.any(Number),
              to_ts: expect.any(Number),
            }),
          }),
        }),
      );
    });
  });

  it('should check MagicX availability from cache for checkout dashboard', async () => {
    jest.spyOn(require('react-router-dom'), 'useParams').mockReturnValue({
      activetab: 'overview',
      insights_dashboard: 'checkout',
    });

    renderApp();

    await waitFor(() => {
      expect(getInsightsDataWithFlags).toHaveBeenCalled();
    });
  });
});
