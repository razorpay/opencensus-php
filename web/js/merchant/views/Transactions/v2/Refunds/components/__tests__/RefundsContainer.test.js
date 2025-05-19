import { screen, waitFor } from 'test-utils';
import { mockRefundAPIResponse } from 'merchant/views/Transactions/v2/Refunds/components/__tests__/mocks/handlers';
import { renderApp } from 'merchant/views/Transactions/v2/Refunds/components/__tests__/mocks/fixtures/RefundsContainer';
import { mockStoreHierarchyAPIResponse } from 'merchant/views/Transactions/v2/Payments/__tests__/mocks/handlers';

jest.mock('merchant/utils/omniUtils', () => ({
  isOmniChannelMerchant: jest.fn().mockReturnValue(true),
}));

jest.mock('merchant/containers/Home/RTUX/utils', () => ({
  isOmniHomepageEnabled: jest.fn().mockReturnValue(true),
}));

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

  test('should show an error notification when there is an error fetching store hierarchy', async () => {
    const error = 'Failed to fetch store hierarchy';
    mockStoreHierarchyAPIResponse({
      error,
    });
    
    jest.spyOn(require('@tanstack/react-query'), 'useQuery').mockReturnValue({
      data: undefined,
      error: new Error(error),
      isLoading: false,
    });

    renderApp();
    
    await waitFor(() => {
      expect(
        screen.getByText(
          'Unable to fetch stores information at this moment, please try again later.',
        ),
      ).toBeInTheDocument();
    });
  });
});
