import React from 'react';

import 'react-dates/initialize';
import { render, screen, waitFor } from 'common/services/test/test-utils';
import { getInitialUserOrgState } from 'common/tests/utils';
import { REQUEST_EPOCH_APRIL_2023 } from 'merchant/views/PartnerDashboard/Commissions/__test__/mocks/fixtures';
import { mockCommisionsListOnceForCurlec } from 'merchant/views/PartnerDashboard/Commissions/__test__/mocks/once-handlers';
import EarningsTransactionalList from 'merchant/views/PartnerDashboard/Earnings/Transactional/List';

const defaultProps = {
  location: {
    search: '',
  },
};
describe('test suite for Earnings List', () => {
  beforeEach(() => {
    // set system time for fixed query params
    jest.useFakeTimers('modern');
    jest.setSystemTime(REQUEST_EPOCH_APRIL_2023);
  });
  afterEach(() => {
    jest.useRealTimers();
  });

  // Note: the data fixtures for relevant api calls are present near the common component's test i.e. Commissions/__test__/mocks
  test('should render transactional earnings for razorpay', async () => {
    const state = getInitialUserOrgState({ isRzpOrg: true });
    render(<EarningsTransactionalList {...defaultProps} />, {
      initialState: { session: state },
    });
    await waitFor(() => {
      expect(screen.getByTestId('amount-comm_G8vny1PSg5hY5Q')).toHaveTextContent('₹ 0.01');
      expect(screen.getByText('Dec 4, 2020')).toBeInTheDocument();
    });
    // Check for negative refund amount
    expect(screen.getByTestId('amount-comm_MWDbnTWLqZrpMV').closest('td')).toHaveTextContent(
      '- ₹ 5.90₹ - ₹ (INR)',
    );
  });

  test('should render transactional earnings for curlec', async () => {
    mockCommisionsListOnceForCurlec();
    const state = getInitialUserOrgState({ isRzpOrg: false });
    render(<EarningsTransactionalList {...defaultProps} />, {
      initialState: { session: state },
    });
    await waitFor(() => {
      expect(screen.getByTestId('amount-comm_G8vny1PSg5hY5Q')).toHaveTextContent('RM 0.01');
      expect(screen.getByText('Dec 4, 2020')).toBeInTheDocument();
    });
  });
});
