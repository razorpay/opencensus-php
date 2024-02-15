import React from 'react';

import 'react-dates/initialize';
import { render, screen, waitFor } from 'common/services/test/test-utils';
import { getInitialUserOrgState } from 'common/tests/utils';
import { REQUEST_EPOCH_APRIL_2023 } from 'merchant/views/PartnerDashboard/Commissions/__test__/mocks/fixtures';
import EarningsDailyList from 'merchant/views/PartnerDashboard/Earnings/Daily/List';
jest.mock('common/ui/DateRangePicker', () => () => <div>DateRangePicker</div>);

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
  test('should render daily earnings for razorpay', async () => {
    const state = getInitialUserOrgState({ isRzpOrg: true });
    render(<EarningsDailyList {...defaultProps} />, {
      initialState: { session: state },
    });
    // Lazy loaded components
    await waitFor(() => expect(screen.queryByRole('loader')).not.toBeInTheDocument());
    // API call
    await waitFor(() => expect(screen.queryByTestId('spinner')).not.toBeInTheDocument());
    expect(screen.getByTestId('amount-1679250600')).toHaveTextContent('₹ 40.00');
    expect(screen.getByText('Mar 27, 2023')).toBeInTheDocument();
  });

  test('should render daily earnings for curlec', async () => {
    const state = getInitialUserOrgState({ isRzpOrg: false });
    render(<EarningsDailyList {...defaultProps} />, {
      initialState: { session: state },
    });
    // Lazy loaded components
    await waitFor(() => expect(screen.queryByRole('loader')).not.toBeInTheDocument());
    // API call
    await waitFor(() => expect(screen.queryByTestId('spinner')).not.toBeInTheDocument());
    expect(screen.getByTestId('amount-1679250600')).toHaveTextContent('RM 40.00');
    expect(screen.getByText('Mar 27, 2023')).toBeInTheDocument();
  });
});
