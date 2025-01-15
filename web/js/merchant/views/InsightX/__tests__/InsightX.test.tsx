import React from 'react';
import { embedDashboard } from '@superset-ui/embedded-sdk';

import { userEvent, render, screen, waitFor } from 'test-utils';

import InsightX from '..';
import { DOCUMENTATION_ROUTES, INSIGHTX_TABS } from '../constants';
import { useSupersetDashboard } from '../hooks/useSupersetDashboard';

// Mock external dependencies
jest.mock('@superset-ui/embedded-sdk', () => ({
  embedDashboard: jest.fn(),
}));

jest.mock('../hooks/useSupersetDashboard', () => ({
  useSupersetDashboard: jest.fn(),
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

const mockRefetch = jest.fn().mockResolvedValue({
  data: 'mockToken',
  isLoading: false,
  isError: false,
  error: null,
});

(useSupersetDashboard as jest.Mock).mockReturnValue({
  refetch: mockRefetch,
  data: 'mockToken',
  isLoading: false,
  isError: false,
  error: null,
});

describe('Testing InsightX Component', () => {
  const renderApp = () => {
    return render(<InsightX />);
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
    rerender(<InsightX />);

    expect(progressBar).not.toBeInTheDocument();
  });

  it('should change tabs successfully', () => {
    renderApp();
    const tab = screen.getByText('Cards');
    userEvent.click(tab);
    expect(screen.getByText('Cards')).toBeInTheDocument();
  });

  it('should render documentation link correctly', () => {
    renderApp();
    const tabName = INSIGHTX_TABS[0].name;
    const tabButton = screen.getByText(tabName);
    userEvent.click(tabButton);

    const documentationButton = screen.getByText('Documentation');
    expect(documentationButton).toBeInTheDocument();

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

  it('should call guest token refetch and re-embeds the dashboard on tab change', async () => {
    renderApp();

    // Initially embedDashboard is called once during initial render
    expect(embedDashboard).toHaveBeenCalledTimes(1);

    const tab = screen.getByText('UPI'); // Change tab to "UPI" (or any other tab in INSIGHTX_TABS)
    userEvent.click(tab);
    expect(screen.getByText('UPI')).toBeInTheDocument();
    await waitFor(() => {
      expect(embedDashboard).toHaveBeenCalledTimes(2); // Initial call + re-embed on tab change
    });
  });
});
