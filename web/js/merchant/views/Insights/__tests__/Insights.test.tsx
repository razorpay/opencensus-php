import React from 'react';
import { embedDashboard } from '@superset-ui/embedded-sdk';
import { userEvent, render, screen, waitFor } from 'test-utils';
import Insights from 'merchant/views/Insights/Insights';
import { DOCUMENTATION_ROUTES, SUCCESS_RATE_TABS } from 'merchant/views/Insights/constants';
import { useSupersetDashboard } from 'merchant/views/Insights/hooks/useSupersetDashboard';
import { useInsightsSplitzExperiments } from 'merchant/views/Insights/hooks/useInsightsSplitzExperiments';

jest.mock('@superset-ui/embedded-sdk', () => ({
  embedDashboard: jest.fn(),
}));

jest.mock('../hooks/useSupersetDashboard', () => ({
  useSupersetDashboard: jest.fn(),
}));

jest.mock('../hooks/useInsightsSplitzExperiments', () => ({
  useInsightsSplitzExperiments: jest.fn(),
}));

jest.mock('../DateRangePicker', () => ({
  DateRangePicker: ({ selectedDateCallback }) => (
    <div
      data-testid="date-range-picker-input"
      onClick={() => {
        const now = Date.now();
        const sevenDaysAgo = now - 7 * 24 * 60 * 60 * 1000;
        selectedDateCallback({
          from: Math.floor(sevenDaysAgo / 1000),
          to: Math.floor(now / 1000),
        });
      }}
    >
      Select Date Range
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
    (useInsightsSplitzExperiments as jest.Mock).mockReturnValue({
      isExperimentEnabled: false,
      isInsightsCheckoutMagicXEnabled: false,
      isInsightsCheckoutEnabled: false,
      isInsightsSuccessRateEnabled: false,
    });

    (useSupersetDashboard as jest.Mock).mockReturnValue({
      refetch: mockRefetch,
      data: 'mockToken',
      isLoading: false,
      isError: false,
      error: null,
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

  it('should pass correct date range to embedDashboard', async () => {
    renderApp();

    const dateRangePicker = screen.getByTestId('date-range-picker-input');
    userEvent.click(dateRangePicker);

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
});
