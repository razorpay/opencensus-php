import React from 'react';
import { SuspenseWithLoader } from '@dashboard/shared-ui/components';
import { render } from 'apps/self-serve/src/services/test/test-utils';

import EntitiesOverview from 'apps/self-serve/src/App/Transactions/v2/EntitiesOverview';
import { TransactionsEntityRoute } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
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

// Note: These test modules are part of web along with their tests. Will be running these tests after migration.
// jest.mock(
//   'apps/self-serve/src/App/Transactions/v1/SuccessRate',
//   () =>
//     function SuccessRate() {
//       return <div>SuccessRate</div>;
//     },
// );

// jest.mock(
//   'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsContainer/PaymentsContainer',
//   () =>
//     function PaymentsContainer() {
//       return <div>PaymentsContainer</div>;
//     },
// );

// jest.mock(
//   'apps/self-serve/src/App/Transactions/v2/Refunds/components/RefundsContainer',
//   () =>
//     function RefundsContainer() {
//       return <div>RefundsContainer</div>;
//     },
// );

// jest.mock(
//   'apps/self-serve/src/App/Transactions/v1/Disputes/List',
//   () =>
//     function List() {
//       return <div>DisputesList</div>;
//     },
// );

// jest.mock(
//   'apps/self-serve/src/App/Transactions/v1/BatchRefunds/List',
//   () =>
//     function List() {
//       return <div>BatchRefundsList</div>;
//     },
// );

// jest.mock(
//   'apps/self-serve/src/App/Transactions/v1/BatchRefunds/BatchUpload',
//   () =>
//     function BatchUpload() {
//       return <div>BatchRefundsUpload</div>;
//     },
// );

// jest.mock(
//   'apps/self-serve/src/App/Transactions/v2/Analytics/EntityAnalytics',
//   () =>
//     function EntityAnalytics() {
//       return <div>EntityAnalytics</div>;
//     },
// );

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
