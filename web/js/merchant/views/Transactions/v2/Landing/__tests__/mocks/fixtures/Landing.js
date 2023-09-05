import cloneDeep from 'lodash/cloneDeep';
import { render } from 'test-utils';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import store from 'merchant/store';
import Landing from 'merchant/views/Transactions/v2/Landing';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';
import 'jest-location-mock';

const { PAYMENTS, ORDERS } = TransactionsEntityRoute;

export const routeTestData = [
  ['PaymentsContainer', PAYMENTS],
  ['OrdersList', ORDERS],
];

jest.mock(
  'merchant/views/Transactions/v2/Payments/components/PaymentsContainer/PaymentsContainer',
  () => () => <div>PaymentsContainer</div>,
);

jest.mock('merchant/views/Transactions/v2/Analytics/LandingAnalytics', () => () => (
  <div>LandingAnalytics</div>
));

jest.mock('merchant/views/Transactions/v1/Orders/List', () => () => <div>OrdersList</div>);

const storeData = store.getState();
const getStateSpy = jest.spyOn(store, 'getState');
getStateSpy.mockImplementation(() => {
  const clonedStore = cloneDeep(storeData);
  clonedStore.session.user = {
    ...clonedStore.session.user,
    isAllowedView: jest.fn(() => true),
  };
  return clonedStore;
});

export const renderApp = ({ pathname } = {}) => {
  return render(
    <SuspenseWithLoader>
      <Landing />
    </SuspenseWithLoader>,
    {
      historyOptions: {
        initialEntries: [
          {
            pathname,
          },
        ],
      },
    },
  );
};
