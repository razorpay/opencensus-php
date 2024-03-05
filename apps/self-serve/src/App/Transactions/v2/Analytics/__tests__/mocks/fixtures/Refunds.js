import React from 'react';
import { screen, render, waitFor } from 'apps/self-serve/src/services/test/test-utils';
import Refund from 'apps/self-serve/src/App/Transactions/v2/Analytics/EntityAnalytics/Refunds';

jest.mock('@dashboard/shared-ui/hooks', () => ({
  useMobile: jest.fn(() => false),
}));

const mockFetchRefundData = jest.fn();
let mockLoading = false;
let mockFailed = false;

export const mockrefundsData = {
  refunded: {
    count: 243,
    amount: 3456,
  },
  processing: {
    count: 54,
    amount: 6556,
  },
  failed: {
    count: 67,
    amount: 32143,
  },
};

jest.mock('apps/self-serve/src/App/Transactions/v2/Analytics/hooks', () => ({
  useRefundsData: () => ({
    mockrefundsData,
    fetchRefundData: mockFetchRefundData,
    loading: mockLoading,
    failed: mockFailed,
  }),
}));

export function modifyData({ kind = 'loading', value = true }) {
  if (kind === 'loading') {
    mockLoading = value;
  } else {
    mockFailed = value;
  }
}

export const renderApp = ({ session } = {}) => {
  const renderOutput = render(<Refund />, {
    initialState: {
      session: {
        user: {
          isTransactionsV2Enabled: true,
          merchant: {
            currency: 'INR',
          },
        },
        ...session,
      },
    },
  });
  return renderOutput;
};

export const assertRefetchData = async ({ isNegate = false } = {}) => {
  await waitFor(() => {
    if (isNegate) {
      expect(mockFetchRefundData).not.toHaveBeenCalled();
    } else {
      expect(mockFetchRefundData).toHaveBeenCalled();
    }
  });
};

export const assertHeadings = async () => {
  await waitFor(() => {
    expect(screen.queryAllByText('Refunds')[0]).toBeInTheDocument();
    expect(screen.queryAllByText('Refunded')[0]).toBeInTheDocument();
    expect(screen.queryAllByText('Processing')[0]).toBeInTheDocument();
    expect(screen.queryAllByText('Failed')[0]).toBeInTheDocument();
  });
};

export const assertRefundData = async () => {
  await assertHeadings();
  await waitFor(() => {
    expect(
      screen.queryByText(`from ${mockrefundsData.refunded.count} processed refunds`),
    ).toBeInTheDocument();
    expect(
      screen.queryByText(`from ${mockrefundsData.processing.count} refunds`),
    ).toBeInTheDocument();
    expect(screen.queryByText(`from ${mockrefundsData.failed.count} refunds`)).toBeInTheDocument();
  });
};
