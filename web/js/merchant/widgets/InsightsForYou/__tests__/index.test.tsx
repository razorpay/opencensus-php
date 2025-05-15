import React, { Suspense } from 'react';

import ErrorBoundary from '@libs/web-nexus/common/new-ui/ErrorBoundary';
import { INSIGHTS_FOR_YOU_MOCK_RESPONSE } from 'merchant/containers/Home/RTUX/__tests__/mocks';
import { InsightsForYou } from 'merchant/widgets/InsightsForYou';
import { render, screen, userEvent, waitFor } from 'test-utils';

jest.mock('merchant/utils/omniUtils', () => ({
  isOmniChannelMerchant: jest.fn().mockReturnValue(true),
}));

const mockRetry = jest.fn();
jest.mock('merchant/widgets/hooks', () => ({ useRetryWidget: () => [false, mockRetry] }));

const initProps = { queryKey: [], isLoading: false, ...INSIGHTS_FOR_YOU_MOCK_RESPONSE };

describe('Widgets->InsightsForYou', () => {
  beforeAll(() => {
    const mockIntersectionObserver = jest.fn();
    mockIntersectionObserver.mockReturnValue({ observe: jest.fn(), unobserve: jest.fn() });
    window.IntersectionObserver = mockIntersectionObserver;
  });

  const renderApp = (defaultProps = {}) => {
    return render(
      <ErrorBoundary>
        <Suspense fallback="">
          <InsightsForYou {...initProps} {...defaultProps} />
        </Suspense>
      </ErrorBoundary>,
      {},
    );
  };

  test('should display cards for loading state', async () => {
    const { rerender } = renderApp({ isLoading: true });
    expect(screen.getByTestId('insights-for-you-widget-loader')).toBeInTheDocument();
    rerender(
      <ErrorBoundary>
        <Suspense fallback="">
          <InsightsForYou {...initProps} isLoading={false} />
        </Suspense>
      </ErrorBoundary>,
    );
    expect(screen.queryByTestId('insights-for-you-widget-loader')).not.toBeInTheDocument();
  });

  test('should render cards after loading', async () => {
    renderApp();
    expect(screen.queryByTestId('insights-for-you-widget-loader')).not.toBeInTheDocument();
    expect(screen.getByText('Insights for you')).toBeInTheDocument();
    expect(screen.getByText('Payments Overview')).toBeInTheDocument();
    expect(screen.getByText('Top Insights')).toBeInTheDocument();
    expect(screen.getByText('In Person Business Performance')).toBeInTheDocument();
  });

  test('should retry widget on error', async () => {
    renderApp({ error: { message: 'Error' } });
    const retryButton = await screen.findAllByRole('button', { name: /try again/i });
    await userEvent.click(retryButton[0]);
    await waitFor(() => {
      expect(mockRetry).toHaveBeenCalled();
    });
  });
});
