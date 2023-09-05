import {
  mockFetchPaymentItems,
  useMobileSpy,
  renderApp,
} from 'merchant/views/Transactions/v2/Payments/__tests__/mocks/fixtures/PaymentsTable';
import { screen, waitFor } from 'test-utils';

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
