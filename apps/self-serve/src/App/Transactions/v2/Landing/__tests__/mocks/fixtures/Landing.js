// TODO: Fix the test properly, currently it's skipped in master

import React from 'react';
import  SuspenseWithLoader  from '@libs/web-nexus/common/new-ui/SuspenseWithLoader';
import { render } from 'apps/self-serve/src/services/test/test-utils';

// import TransactionRoute from 'apps/self-serve/src/App/Transactions/__tests__/mocks/TransactionRoute';
import { TransactionsEntityRoute } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import 'jest-location-mock';

const { PAYMENTS, ORDERS } = TransactionsEntityRoute;

export const routeTestData = [
  ['PaymentsContainer', PAYMENTS],
  ['OrdersList', ORDERS],
];

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsContainer/PaymentsContainer',
  () =>
    function PaymentsContainer() {
      return <div>PaymentsContainer</div>;
    },
);

jest.mock(
  'apps/self-serve/src/App/Transactions/v2/Analytics/LandingAnalytics',
  () =>
    function LandingAnalytics() {
      return <div>LandingAnalytics</div>;
    },
);

export const renderApp = ({ pathname } = {}) => {
  return render(<SuspenseWithLoader>{/* <TransactionRoute /> */}</SuspenseWithLoader>, {
    initialEntries: [pathname ?? '/payments'],
    renderViaRouteGuard: false,
  });
};
