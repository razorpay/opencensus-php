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

const BatchPaymentsList = lazy(
  () =>
    import(
      /* webpackChunkName: "BatchPaymentsList" */ 'merchant/views/Transactions/v1/BatchPayments/List'
    ),
);

const OrdersList = lazy(
  () => import(/* webpackChunkName: "OrdersList" */ 'merchant/views/Transactions/v1/Orders/List'),
);

const UploadInvoice = lazy(
  () =>
    import(/* webpackChunkName: "UploadInvoice" */ 'merchant/views/Transactions/v1/UploadInvoice'),
);

const B2bPaymentsList = lazy(
  () =>
    import(
      /* webpackChunkName: "B2bPaymentsList" */ 'merchant/views/Transactions/v1/B2bPayments/List'
    ),
);

const { ORDERS } = Page;
const {
  PAYMENTS: PAYMENTS_ROUTE,
  ORDERS: ORDERS_ROUTE,
  BATCH_PAYMENTS,
  UPLOAD_INVOICES,
  INVOICES,
} = TransactionsEntityRoute;

const Landing = (): JSX.Element => {
  return (
    <div className="tabbed-container">
      <LandingPageAnalyticsOverview />
      <StyledTabHeader id="transactions-header">
        <StyledTabItem
          to={PAYMENTS_ROUTE}
          onClick={trackTransactionsTabClick(PAYMENTS_ROUTE)}
          exact
        >
          Payments
        </StyledTabItem>

        <ShowWhen
          featureEnabled="direct_debit"
          additionalCondition={(usr) => usr.isAllowedView('payments_batch_uploads')}
        >
          <StyledTabItem to={BATCH_PAYMENTS} onClick={trackTransactionsTabClick(BATCH_PAYMENTS)}>
            Batch Payments
          </StyledTabItem>
        </ShowWhen>

        <ShowWhen additionalCondition={(usr) => usr.isAllowedView(ORDERS)}>
          <StyledTabItem to={ORDERS_ROUTE} onClick={trackTransactionsTabClick(ORDERS_ROUTE)}>
            Orders
          </StyledTabItem>
        </ShowWhen>

        <ShowWhen
          additionalCondition={(user) => user.international && user.isAllowedView('b2b_payments')}
        >
          <StyledTabItem to={UPLOAD_INVOICES} onClick={trackTransactionsTabClick(UPLOAD_INVOICES)}>
            Upload Invoices
          </StyledTabItem>
        </ShowWhen>

        <ShowWhen
          featureEnabled="opgsp_import_flow"
          additionalCondition={(usr) => usr.isAllowedView('b2b_payments')}
        >
          <StyledTabItem to={INVOICES} onClick={trackTransactionsTabClick(INVOICES)}>
            Invoices
          </StyledTabItem>
        </ShowWhen>
      </StyledTabHeader>
      <StyledContent className="content transactions-content">
        <ErrorBoundary resetOnProps>
          <Switch>
            <ShowWhenRoute path={`${BATCH_PAYMENTS}/:mode`} component={BatchPaymentsList} />
            <ShowWhenRoute path={BATCH_PAYMENTS} component={BatchPaymentsList} />
            <ShowWhenRoute
              path={INVOICES}
              component={UploadInvoice}
              additionalCondition={(usr) => usr.isAllowedView('b2b_payments')}
            />
            <ShowWhenRoute
              path={UPLOAD_INVOICES}
              component={B2bPaymentsList}
              additionalCondition={(usr) => usr.isAllowedView('b2b_payments')}
            />
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
