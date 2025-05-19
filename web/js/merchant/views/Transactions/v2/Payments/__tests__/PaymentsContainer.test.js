import { renderApp } from 'merchant/views/Transactions/v2/Payments/__tests__/mocks/fixtures/PaymentsContainer';
import { mockPaymentAPIResponse, mockStoreHierarchyAPIResponse } from 'merchant/views/Transactions/v2/Payments/__tests__/mocks/handlers';
import { screen, waitFor } from 'test-utils';

jest.mock('merchant/utils/omniUtils', () => ({
  isOmniChannelMerchant: jest.fn().mockReturnValue(true),
}));

jest.mock('merchant/containers/Home/RTUX/utils', () => ({
  isOmniHomepageEnabled: jest.fn().mockReturnValue(true),
}));

describe('PaymentsContainer', () => {
  test('should render the loading view initially', async () => {
    renderApp();
    await waitFor(() => {
      expect(
        screen.getByRole('progressbar', {
          name: 'Loading payments',
        }),
      ).toBeInTheDocument();
    });
  });

  test('should render the FTUX view when there are no payments', async () => {
    mockPaymentAPIResponse({
      count: 0,
    });
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Start collecting payments')).toBeInTheDocument();
    });
  });

  test('should render the list view when there are payments', async () => {
    mockPaymentAPIResponse({
      count: 1,
    });
    renderApp();
    await waitFor(() => {
      expect(screen.getByTestId('payments-filter')).toBeInTheDocument();
    });
  });

  test('should render the failed payments view when the "failed" route is active', async () => {
    const pathname = '/failed-payments';
    const initialEntries = ['/failed-payments'];
    mockPaymentAPIResponse({
      count: 0,
    });
    renderApp({}, pathname, initialEntries);
    await waitFor(() => {
      expect(screen.getByText('Track your failed payments')).toBeInTheDocument();
    });
  });

  test('should show an error notification when there is an error fetching payments information', async () => {
    const error = 'Failed to fetch payments';
    mockPaymentAPIResponse({
      error,
    });
    renderApp();
    await waitFor(() => {
      expect(
        screen.getAllByText(
          'Unable to fetch payments information at this moment, please try again later.',
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
