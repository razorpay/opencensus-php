import React, { useEffect } from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import { useRefundsData } from 'apps/self-serve/src/App/Transactions/v2/Analytics/hooks';

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
      {loading ? <div data-testid="loading">Loading...</div> : null}
      {failed ? <div data-testid="failed">Failed...</div> : null}
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
