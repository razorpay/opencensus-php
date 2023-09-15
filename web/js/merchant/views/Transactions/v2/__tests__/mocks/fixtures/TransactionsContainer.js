import { render } from 'test-utils';

import TransactionsContainer from 'merchant/views/Transactions/v2/TransactionsContainer';
import 'jest-location-mock';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';

const {
  PAYMENTS,
  ORDERS,
  FAILED_PAYMENTS,
  DISPUTES,
  SUCCESS_RATE,
  REFUNDS,
  BATCH_REFUNDS,
  BATCH_REFUNDS_UPLOAD,
} = TransactionsEntityRoute;

export const routeTestData = [
  ['Landing', PAYMENTS],
  ['Landing', ORDERS],
  ['EntitiesOverview', FAILED_PAYMENTS],
  ['EntitiesOverview', DISPUTES],
  ['EntitiesOverview', SUCCESS_RATE],
  ['EntitiesOverview', REFUNDS],
  ['EntitiesOverview', BATCH_REFUNDS],
  ['EntitiesOverview', BATCH_REFUNDS_UPLOAD],
];

jest.mock('merchant/views/Transactions/v2/Landing', () => () => <div>Landing</div>);

jest.mock('merchant/views/Transactions/v2/EntitiesOverview', () => () => (
  <div>EntitiesOverview</div>
));

window.scrollTo = jest.fn();

export const renderApp = ({ pathname } = {}) => {
  return render(<TransactionsContainer />, {
    historyOptions: {
      initialEntries: [
        {
          pathname,
        },
      ],
    },
  });
};
