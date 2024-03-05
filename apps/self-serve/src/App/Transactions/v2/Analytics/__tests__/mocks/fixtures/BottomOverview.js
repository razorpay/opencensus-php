import React from 'react';
import { createMemoryHistory } from 'history';
// TODO: @Abhijeet to confirm
import { render, waitFor } from 'apps/self-serve/src/services/test/test-utils';

import BottomAnalyticsOverview from 'apps/self-serve/src/App/Transactions/v2/Analytics/LandingAnalytics/BottomOverview';
import { LAST_7_DAYS } from 'apps/self-serve/src/App/Transactions/v2/common/constants';

const mockRefetch = jest.fn();
export const defaultProps = {
  mode: 'live',
  currency: 'INR',
  refetchData: mockRefetch,
  durationOption: {
    title: 'Last 7 days',
    value: LAST_7_DAYS,
  },
};

export const tiles = ['Refunds', 'Disputes', 'Failed'];
export const tilesToPath = {
  Refunds: '/refunds',
  Disputes: '/disputes',
  Failed: '/failed-payments',
};

const history = createMemoryHistory();
history.push = jest.fn();
export const renderApp = ({ loading = false, failed = false, props } = {}) => {
  const data = {
    refund: {
      amount: 100,
      loading,
      failed,
      count: 5,
    },
    disputes: {
      amount: 200,
      loading,
      failed,
      open: 3,
      underReview: 2,
    },
    failed: {
      amount: 300,
      loading,
      failed,
      isAmount: false,
    },
  };

  render(<BottomAnalyticsOverview {...defaultProps} {...props} data={data} />, {
    history,
  });
};

export const assertFetchData = async (name) => {
  await waitFor(() => {
    expect(mockRefetch).toBeCalledWith(name);
  });
};

export const assertRedirect = async (name) => {
  await waitFor(() => {
    expect(history.push.mock.calls[0][0]).toMatchObject({
      hash: '',
      pathname: tilesToPath[name],
      search: '',
    });
  });
};
