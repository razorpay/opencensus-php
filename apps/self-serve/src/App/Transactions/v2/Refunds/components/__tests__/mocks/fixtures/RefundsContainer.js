import React from 'react';
import { render } from 'apps/self-serve/src/services/test/test-utils';
import RefundsContainer from 'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundsContainer';

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundsListFilter',
  () =>
    function RefundsListFilter({ loading }) {
      return loading ? <div>Loading...</div> : <div>Refunds List Filter</div>;
    },
);

export const renderApp = (props = {}) => {
  return render(<RefundsContainer location={{ pathname: '/' }} {...props} />);
};
