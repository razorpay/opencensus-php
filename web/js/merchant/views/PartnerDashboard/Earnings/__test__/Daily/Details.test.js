import React from 'react';
import { render, screen, waitFor } from 'common/services/test/test-utils';
import EarningsDailyEntity from 'merchant/views/PartnerDashboard/Earnings/Daily/Details';
import { getInitialUserOrgState } from 'common/tests/utils';

const defaultProps = {
  timestamp: 1679941800,
  location: {
    search: '',
  },
};
describe('test suite for Earnings Detail', () => {
  // Note: the data fixtures for relevant api calls are present near the common component's test i.e. Commissions/__test__/mocks
  test('should render earnings detail for razorpay', async () => {
    const state = getInitialUserOrgState({ isRzpOrg: true });
    render(<EarningsDailyEntity {...defaultProps} />, {
      initialState: { session: state },
    });
    await waitFor(() => {
      expect(screen.getByTestId('daily-details-panel')).toBeVisible();
      expect(screen.getByText('Total Transaction Amount')).toBeInTheDocument();
      expect(screen.getByTestId('amount-daily-details')).toHaveTextContent('₹ 20.00');
    });
  });
  test('should render earnings detail for curlec', async () => {
    const state = getInitialUserOrgState({ isRzpOrg: false });
    render(<EarningsDailyEntity {...defaultProps} />, {
      initialState: { session: state },
    });
    await waitFor(() => {
      expect(screen.getByTestId('daily-details-panel')).toBeVisible();
      expect(screen.getByText('Total Transaction Amount')).toBeInTheDocument();
      expect(screen.getByTestId('amount-daily-details')).toHaveTextContent('RM 20.00');
    });
  });
});
