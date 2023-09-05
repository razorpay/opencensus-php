import React, { useEffect } from 'react';
import { useRefundsData } from 'merchant/views/Transactions/v2/Analytics/hooks';
import { render } from 'test-utils';

const App = ({ dataCallback, isRefundPendingEnabled }) => {
  const { refundsData, fetchRefundData, loading, failed } = useRefundsData({
    isRefundPendingEnabled,
  });
  useEffect(() => {
    if (dataCallback) {
      dataCallback({
        refundsData,
      });
    }
  }, [refundsData]);
  return (
    <div>
      {loading && <div data-testid="loading">Loading...</div>}
      {failed && <div data-testid="failed">Failed...</div>}
      <button onClick={() => fetchRefundData({})}>Fetch</button>
    </div>
  );
};

export const renderApp = (props = {}) => {
  render(<App {...props} />);
};

export const expectedHookResponse = {
  refundsData: {
    refunded: {
      count: 1,
      amount: 100,
    },
    processing: {
      count: 0,
      amount: 0,
    },
    failed: {
      count: 0,
      amount: 0,
    },
  },
};
