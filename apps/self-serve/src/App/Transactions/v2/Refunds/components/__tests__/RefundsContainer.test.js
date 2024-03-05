import { screen, waitFor } from 'apps/self-serve/src/services/test/test-utils';
import { mockRefundAPIResponse } from 'apps/self-serve/src/App/Transactions/v2/Refunds/components/__tests__/mocks/handlers';
import { renderApp } from 'apps/self-serve/src/App/Transactions/v2/Refunds/components/__tests__/mocks/fixtures/RefundsContainer';

describe('RefundsContainer', () => {
  test('should render the loading view initially', async () => {
    renderApp();
    await waitFor(() => {
      expect(
        screen.getByRole('progressbar', {
          name: 'Loading refunds',
        }),
      ).toBeInTheDocument();
    });
  });

  test('should render the FTUX view when there are no refunds', async () => {
    mockRefundAPIResponse({
      count: 0,
    });
    renderApp();
    await waitFor(() => {
      expect(screen.getByRole('link', { name: 'Refunds guide' })).toHaveAttribute(
        'href',
        'https://razorpay.com/docs/payments/refunds/',
      );
    });
  });

  test('should render the list view when there are refunds', async () => {
    mockRefundAPIResponse({
      count: 1,
    });
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Refunds List Filter')).toBeInTheDocument();
    });
  });

  test('should show an error notification when there is an error fetching refunds information', async () => {
    const error = 'Failed to fetch refunds';
    mockRefundAPIResponse({
      error,
    });
    renderApp();
    await waitFor(() => {
      expect(
        screen.getAllByText(
          'Unable to fetch refunds information at this moment, please try again later.',
        )[0],
      ).toBeInTheDocument();
    });
    await waitFor(() => {
      expect(screen.getByTestId('Notification--error')).toBeInTheDocument();
    });
  });
});
