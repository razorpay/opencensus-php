import React, { useEffect } from 'react';
import { useDisputesData } from 'merchant/views/Transactions/v2/Analytics/hooks';
import { render } from 'test-utils';

const App = ({ dataCallback }) => {
  const { disputeData, fetchDisputesData, loading, failed } = useDisputesData({
    mode: 'live',
  });
  useEffect(() => {
    if (dataCallback) {
      dataCallback({
        disputeData,
      });
    }
  }, [disputeData]);
  return (
    <div>
      {loading && <div data-testid="loading">Loading...</div>}
      {failed && <div data-testid="failed">Failed...</div>}
      <button onClick={() => fetchDisputesData({})}>Fetch</button>
    </div>
  );
};

export const renderApp = (props = {}) => {
  render(<App {...props} />);
};
