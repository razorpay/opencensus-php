import React, { useEffect } from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import { useDisputesData } from 'apps/self-serve/src/App/Transactions/v2/Analytics/hooks';

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
      {loading ? <div data-testid="loading">Loading...</div> : null}
      {failed ? <div data-testid="failed">Failed...</div> : null}
      <button onClick={() => fetchDisputesData({})}>Fetch</button>
    </div>
  );
};

export const renderApp = (props = {}) => {
  render(<App {...props} />);
};
