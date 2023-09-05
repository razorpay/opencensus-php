import React from 'react';
import { Route, Switch } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import lazy from 'merchant/routes/LazyLoader';
import LandingPageAnalyticsOverview from 'merchant/views/Transactions/v2/Analytics/LandingAnalytics';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';
import {
  StyledContent,
  StyledTabHeader,
  StyledTabItem,
} from 'merchant/views/Transactions/v2/common/styled';
import { trackTransactionsTabClick } from 'merchant/views/Transactions/v2/common/tracking';
import { Page } from 'merchant/views/Transactions/v2/common/types';

const PaymentsContainer = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentsContainer" */ 'merchant/views/Transactions/v2/Payments/components/PaymentsContainer'
    ),
);
const OrdersList = lazy(
  () => import(/* webpackChunkName: "OrdersList" */ 'merchant/views/Transactions/v1/Orders/List'),
);
const { ORDERS } = Page;
const { PAYMENTS: PAYMENTS_ROUTE, ORDERS: ORDERS_ROUTE } = TransactionsEntityRoute;

const Landing = (): JSX.Element => {
  return (
    <div className="tabbed-container">
      <LandingPageAnalyticsOverview />
      <StyledTabHeader id="transactions-header" className="scrollable-tab-header">
        <StyledTabItem
          to={PAYMENTS_ROUTE}
          onClick={trackTransactionsTabClick(PAYMENTS_ROUTE)}
          exact
        >
          Payments
        </StyledTabItem>
        <ShowWhen additionalCondition={(usr) => usr.isAllowedView(ORDERS)}>
          <StyledTabItem to={ORDERS_ROUTE} onClick={trackTransactionsTabClick(ORDERS_ROUTE)}>
            Orders
          </StyledTabItem>
        </ShowWhen>
      </StyledTabHeader>
      <StyledContent className="content transactions-content">
        <ErrorBoundary resetOnProps>
          <Switch>
            <Route path={PAYMENTS_ROUTE} component={PaymentsContainer} />
            <ShowWhenRoute
              path={ORDERS_ROUTE}
              component={OrdersList}
              additionalCondition={(usr) => usr.isAllowedView(ORDERS)}
            />
          </Switch>
        </ErrorBoundary>
      </StyledContent>
    </div>
  );
};

export default Landing;
