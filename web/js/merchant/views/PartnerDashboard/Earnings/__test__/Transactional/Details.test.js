import React from 'react';
import { render, screen, waitFor } from 'common/services/test/test-utils';
import EarningsTransactionalDetails from 'merchant/views/PartnerDashboard/Earnings/Transactional/Details';
import { mockCommissionDetailOnceForCurlec } from 'merchant/views/PartnerDashboard/Commissions/__test__/mocks/once-handlers';
import { getInitialUserOrgState } from 'common/tests/utils';

const defaultProps = {
  id: 'comm_G8vny1PSg5hY5Q',
  location: {
    search: '',
  },
};
describe('test suite for Earnings Transactional Detail', () => {
  // Note: the data fixtures for relevant api calls are present near the common component's test i.e. Commissions/__test__/mocks
  test('should render transactional detail for razorpay', async () => {
    const state = getInitialUserOrgState({ isRzpOrg: true });
    render(<EarningsTransactionalDetails {...defaultProps} />, {
      initialState: { session: state },
    });
    await waitFor(() => {
      expect(screen.getByTestId('transactional-details-panel')).toBeVisible();
      expect(screen.getByText('Earnings from Razorpay')).toBeInTheDocument();
      expect(screen.getByText('Affiliated Account')).toBeInTheDocument();
      expect(screen.getByTestId('amount-transactional-details')).toHaveTextContent('₹ 7.00');
    });
  });

  test('should render transactional detail for curlec', async () => {
    mockCommissionDetailOnceForCurlec();
    const state = getInitialUserOrgState({ isRzpOrg: false });

    render(<EarningsTransactionalDetails {...defaultProps} />, {
      initialState: { session: state },
    });
    await waitFor(() => {
      expect(screen.getByTestId('transactional-details-panel')).toBeVisible();
      expect(screen.getByText('Earnings from Curlec')).toBeInTheDocument();
      expect(screen.getByText('Affiliated Account')).toBeInTheDocument();
      expect(screen.getByTestId('amount-transactional-details')).toHaveTextContent('RM 7.00');
    });
  });
});
