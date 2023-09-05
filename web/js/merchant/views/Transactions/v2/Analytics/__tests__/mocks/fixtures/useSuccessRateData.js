import React, { useEffect } from 'react';
import { useSuccessRateData } from 'merchant/views/Transactions/v2/Analytics/hooks';
import { render } from 'test-utils';

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
      {loading && <div data-testid="loading">Loading...</div>}
      {failed && <div data-testid="failed">Failed...</div>}
      <button onClick={fetchSuccessRateData}>Fetch</button>
    </div>
  );
};

export const renderApp = (props = {}) => {
  render(<App {...props} />);
};
