import { render } from 'test-utils';
import RefundsContainer from 'merchant/views/Transactions/v2/Refunds/components/RefundsContainer';

jest.mock(
  'merchant/views/Transactions/v2/Refunds/components/RefundsListFilter',
  () =>
    ({ loading }) =>
      loading ? <div>Loading...</div> : <div>Refunds List Filter</div>,
);

export const renderApp = (props = {}) => {
  return render(<RefundsContainer location={{ pathname: '/' }} {...props} />);
};
