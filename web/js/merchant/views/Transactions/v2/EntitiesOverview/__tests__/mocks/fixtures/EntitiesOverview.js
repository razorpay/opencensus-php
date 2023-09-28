import cloneDeep from 'lodash/cloneDeep';
import { render } from 'test-utils';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import store from 'merchant/store';
import EntitiesOverview from 'merchant/views/Transactions/v2/EntitiesOverview';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';
import 'jest-location-mock';

const { FAILED_PAYMENTS, DISPUTES, SUCCESS_RATE, REFUNDS, BATCH_REFUNDS, BATCH_REFUNDS_UPLOAD } =
  TransactionsEntityRoute;

export const routeTestData = [
  ['PaymentsContainer', FAILED_PAYMENTS],
  ['DisputesList', DISPUTES],
  ['RefundsContainer', REFUNDS],
  ['BatchRefundsList', BATCH_REFUNDS],
  ['BatchRefundsUpload', BATCH_REFUNDS_UPLOAD],
  ['SuccessRate', SUCCESS_RATE],
];

export const overviewRouteTestData = [
  FAILED_PAYMENTS,
  REFUNDS,
  BATCH_REFUNDS,
  BATCH_REFUNDS_UPLOAD,
];

jest.mock('merchant/views/Transactions/v1/SuccessRate', () => () => <div>SuccessRate</div>);

jest.mock(
  'merchant/views/Transactions/v2/Payments/components/PaymentsContainer/PaymentsContainer',
  () => () => <div>PaymentsContainer</div>,
);

jest.mock('merchant/views/Transactions/v2/Refunds/components/RefundsContainer', () => () => (
  <div>RefundsContainer</div>
));

jest.mock('merchant/views/Transactions/v1/Disputes/List', () => () => <div>DisputesList</div>);

jest.mock('merchant/views/Transactions/v1/BatchRefunds/List', () => () => (
  <div>BatchRefundsList</div>
));

jest.mock('merchant/views/Transactions/v1/BatchRefunds/BatchUpload', () => () => (
  <div>BatchRefundsUpload</div>
));

jest.mock('merchant/views/Transactions/v2/Analytics/EntityAnalytics', () => () => (
  <div>EntityAnalytics</div>
));

const storeData = store.getState();
const getStateSpy = jest.spyOn(store, 'getState');
getStateSpy.mockImplementation(() => {
  const clonedStore = cloneDeep(storeData);
  clonedStore.session.user = {
    ...clonedStore.session.user,
    isAllowedView: jest.fn(() => true),
    findTag: jest.fn((tag) => tag === 'success_rate'),
  };
  return clonedStore;
});

export const renderApp = ({ pathname } = {}) => {
  return render(
    <SuspenseWithLoader>
      <EntitiesOverview />
    </SuspenseWithLoader>,
    {
      initialState: {
        session: {
          mode: 'live',
        },
      },
      initialEntries: [
        {
          pathname,
        },
      ],
    },
  );
};
