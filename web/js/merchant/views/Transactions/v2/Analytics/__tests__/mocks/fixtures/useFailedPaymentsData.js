import React, { useEffect } from 'react';
import { useFailedPaymentsData } from 'merchant/views/Transactions/v2/Analytics/hooks';
import { render } from 'test-utils';

const App = ({ dataCallback }) => {
  const { fetchFailedPaymentsData, failureInfo, failedPaymentsData, loading, failed } =
    useFailedPaymentsData();
  useEffect(() => {
    if (dataCallback) {
      dataCallback({
        failedPaymentsData,
        failureInfo,
      });
    }
  }, [failedPaymentsData]);

  return (
    <div>
      {loading && <div data-testid="loading">Loading...</div>}
      {failed && <div data-testid="failed">Failed...</div>}
      <button onClick={fetchFailedPaymentsData}>Fetch</button>
    </div>
  );
};

export const renderApp = (props = {}) => {
  render(<App {...props} />);
};

export const apiResponse = {
  customer: [
    {
      reason: 'Payment cancelled while in-progress',
      count: 1,
    },
    {
      reason: 'Payment timed-out',
      count: 1,
    },
  ],
  bank: [
    {
      reason: 'Payment declined by bank',
      count: 5,
    },
  ],
  business: [],
  others: [
    {
      reason:
        'Your payment was not successful as this Seller is not allowed to accept payments. We suggest not going ahead with this transaction.',
      count: 7,
    },
    {
      reason: 'Bank technical issue',
      count: 6,
    },
  ],
};

export const invalidApiResponse = {
  customer: [],
  bank: [
    {
      reason: 'Payment cancelled while in-progress',
      count: 0,
    },
    {
      reason: 'Payment cancelled while in-progress',
      count: 0,
    },
  ],
};

export const expectedInvalidHookResponse = {
  failedPaymentsData: 0,
  failureInfo: {
    customer: { value: 0, failure_types: [] },
    bank: { value: 0, failure_types: [] },
    others: { value: 0, failure_types: [] },
  },
};

export const expectedHookResponse = {
  failedPaymentsData: 20,
  failureInfo: {
    bank: { failure_types: ['Payment declined by bank'], value: 5 },
    customer: {
      failure_types: ['Payment cancelled while in-progress', 'Payment timed-out'],
      value: 2,
    },
    others: {
      failure_types: [
        'Your payment was not successful as this Seller is not allowed to accept payments. We suggest not going ahead with this transaction.',
        'Bank technical issue',
      ],
      value: 13,
    },
  },
};
