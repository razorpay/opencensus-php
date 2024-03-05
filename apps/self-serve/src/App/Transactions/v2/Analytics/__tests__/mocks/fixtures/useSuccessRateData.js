import React, { useEffect } from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import { useSuccessRateData } from 'apps/self-serve/src/App/Transactions/v2/Analytics/hooks';

const App = ({ dataCallback }) => {
  const { successRateData, fetchSuccessRateData, loading, failed } = useSuccessRateData();
  useEffect(() => {
    if (dataCallback) {
      dataCallback({
        successRateData,
      });
    }
  }, [successRateData]);
  return (
    <div>
      {loading ? <div data-testid="loading">Loading...</div> : null}
      {failed ? <div data-testid="failed">Failed...</div> : null}
      <button onClick={fetchSuccessRateData}>Fetch</button>
    </div>
  );
};

export const renderApp = (props = {}) => {
  render(<App {...props} />);
};
