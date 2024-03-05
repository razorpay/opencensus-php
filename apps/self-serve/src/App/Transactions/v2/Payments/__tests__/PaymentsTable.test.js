import { screen, waitFor } from 'apps/self-serve/src/services/test/test-utils';
import {
  mockFetchPaymentItems,
  useMobileSpy,
  renderApp,
} from 'apps/self-serve/src/App/Transactions/v2/Payments/__tests__/mocks/fixtures/PaymentsTable';

describe('PaymentsTable', () => {
  test('should renders correct column for desktop', () => {
    const items = mockFetchPaymentItems();
    renderApp({ items });
    [
      'Payment ID',
      'Bank RRN',
      'Customer detail',
      'Created on',
      'Amount',
      'Status',
      'Actions',
    ].forEach((column) => {
      expect(
        screen.getByRole('columnheader', {
          name: column,
        }),
      ).toBeInTheDocument();
    });
  });

  describe('source channel', () => {
    test('should show source channel when user is omni', () => {
      const items = mockFetchPaymentItems();
      renderApp({
        items,
        shouldDisplaySourceChannel: true,
      });
      expect(screen.queryAllByTestId('source-channel').length).toBeGreaterThan(0);
    });

    test('should not show source channel when user is not omni', () => {
      const items = mockFetchPaymentItems();
      renderApp({
        items,
        shouldDisplaySourceChannel: false,
      });
      expect(screen.queryAllByTestId('source-channel').length).toBe(0);
    });
  });

  test('should renders correct column for mobile', () => {
    window.location.pathname = '/failed-payments';
    useMobileSpy.mockImplementation(() => true);
    const items = mockFetchPaymentItems();
    renderApp({ items });
    ['Amount', 'Status', 'Actions'].forEach((column) => {
      expect(
        screen.getByRole('columnheader', {
          name: column,
        }),
      ).toBeInTheDocument();
    });
  });

  test('should not render payments when no items are present', async () => {
    renderApp({ items: [] });
    await waitFor(() => {
      expect(screen.getByText('No payment in selected duration')).toBeInTheDocument();
    });
  });
});
