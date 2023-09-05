import { screen, waitFor } from 'test-utils';
import {
  mockFetchRefundItems,
  renderApp,
  useMobileSpy,
  desktopColumns,
  mobileColumns,
} from 'merchant/views/Transactions/v2/Refunds/components/__tests__/mocks/fixtures/RefundsTable';

describe('RefundsTable', () => {
  test('should renders correct column for desktop', () => {
    const items = mockFetchRefundItems();
    renderApp({ items });
    desktopColumns.forEach((column) => {
      expect(
        screen.getByRole('columnheader', {
          name: column,
        }),
      ).toBeInTheDocument();
    });
  });

  test('should renders correct column for mobile', () => {
    useMobileSpy.mockImplementation(() => true);
    const items = mockFetchRefundItems();
    renderApp({ items });
    mobileColumns.forEach((column) => {
      expect(
        screen.getByRole('columnheader', {
          name: column,
        }),
      ).toBeInTheDocument();
    });
  });

  test('should not render Refunds when no items are present', async () => {
    renderApp({ items: [] });
    await waitFor(() => {
      expect(screen.getByText('No refund in selected duration')).toBeInTheDocument();
    });
  });
});
