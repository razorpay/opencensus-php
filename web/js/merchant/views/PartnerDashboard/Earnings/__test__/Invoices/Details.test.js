import React from 'react';
import { render, screen, waitFor } from 'common/services/test/test-utils';
import EarningsInvoicesDetails from 'merchant/views/PartnerDashboard/Earnings/Invoices/Details';
import { mockInvoiceDetailOnceForCurlec } from 'merchant/views/PartnerDashboard/Commissions/__test__/mocks/once-handlers';
import { getInitialUserOrgState } from 'common/tests/utils';

const defaultProps = {
  id: 'comm_G8vny1PSg5hY5Q',
  location: {
    search: '',
  },
};
describe('test suite for Earnings Invoices Details', () => {
  // Note: the data fixtures for relevant api calls are present near the common component's test i.e. Commissions/__test__/mocks
  test('should render invoice detail for razorpay', async () => {
    const state = getInitialUserOrgState({ isRzpOrg: true });
    render(<EarningsInvoicesDetails {...defaultProps} />, {
      initialState: { session: state },
    });
    await waitFor(() => {
      expect(screen.getByTestId('invoice-details-panel')).toBeVisible();
      expect(screen.getByText('01 Mar 2023 to 31 Mar 2023')).toBeInTheDocument();
      expect(screen.getByTestId('amount-invoice-details')).toHaveTextContent('₹ 6.00');
    });
  });

  test('should render invoice detail for curlec', async () => {
    mockInvoiceDetailOnceForCurlec();
    const state = getInitialUserOrgState({ isRzpOrg: false });
    render(<EarningsInvoicesDetails {...defaultProps} />, {
      initialState: { session: state },
    });
    await waitFor(() => {
      expect(screen.getByTestId('invoice-details-panel')).toBeVisible();
      expect(screen.getByText('01 Mar 2023 to 31 Mar 2023')).toBeInTheDocument();
      expect(screen.getByTestId('amount-invoice-details')).toHaveTextContent('RM 6.00');
    });
  });
});
