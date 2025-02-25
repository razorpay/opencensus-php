import React, { ReactNode, useEffect } from 'react';
import { Outlet, Route, Routes } from 'react-router-dom';
import { Provider } from 'react-redux';

import { ErrorBoundary } from '@libs/shared-ui';
import ShowWhen from "@libs/web-nexus/merchant/components/SharedShowWhen";
import LandingPageAnalyticsOverview from 'apps/self-serve/src/App/Transactions/v2/Analytics/LandingAnalytics';
import PaymentsContainer from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsContainer';
import { TransactionsEntityRoute } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import {
  StyledContent,
  StyledTabHeader,
  StyledTabItem,
} from 'apps/self-serve/src/App/Transactions/v2/common/styled';
import {
  track,
  trackTransactionsTabClick,
} from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { Page } from 'apps/self-serve/src/App/Transactions/v2/common/types';
import store from 'apps/self-serve/src/bootstrap/Store';

const { ORDERS } = Page;
const {
  PAYMENTS: PAYMENTS_ROUTE,
  ORDERS: ORDERS_ROUTE,
  BATCH_PAYMENTS,
  UPLOAD_INVOICES,
  INVOICES,
} = TransactionsEntityRoute;

interface LandingContainerProps {
  children: ReactNode;
}

export const LandingContainer = ({ children }: LandingContainerProps) => {
  useEffect(() => {
    track({
      objectName: 'Transactions Page',
      actionName: 'Rendered',
    });
    setTimeout(() =>
      window.scrollTo({
        top: 0,
        left: 0,
        behavior: 'smooth',
      }),
    );
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <div className="tabbed-container">
      {/* TODO: remove the Provider after completing the migration of the Transactions v2 tabs components */}
      <Provider store={store}>
        <LandingPageAnalyticsOverview />
      </Provider>

      <StyledTabHeader id="transactions-header">
        <StyledTabItem to={PAYMENTS_ROUTE} onClick={trackTransactionsTabClick(PAYMENTS_ROUTE)} end>
          Payments
        </StyledTabItem>

        <ShowWhen
          featureEnabled="direct_debit"
          additionalCondition={(usr: any) => usr.isAllowedView('payments_batch_uploads')}
        >
          <StyledTabItem to={BATCH_PAYMENTS} onClick={trackTransactionsTabClick(BATCH_PAYMENTS)}>
            Batch Payments
          </StyledTabItem>
        </ShowWhen>

        <ShowWhen additionalCondition={(usr: any) => usr.isAllowedView(ORDERS)}>
          <StyledTabItem to={ORDERS_ROUTE} onClick={trackTransactionsTabClick(ORDERS_ROUTE)}>
            Orders
          </StyledTabItem>
        </ShowWhen>

        <ShowWhen
          additionalCondition={(user: any) =>
            user.international && user.isAllowedView('b2b_payments')
          }
        >
          <StyledTabItem to={UPLOAD_INVOICES} onClick={trackTransactionsTabClick(UPLOAD_INVOICES)}>
            Upload Invoices
          </StyledTabItem>
        </ShowWhen>

        <ShowWhen
          featureEnabled="opgsp_import_flow"
          additionalCondition={(usr: any) => usr.isAllowedView('b2b_payments')}
        >
          <StyledTabItem to={INVOICES} onClick={trackTransactionsTabClick(INVOICES)}>
            Invoices
          </StyledTabItem>
        </ShowWhen>
      </StyledTabHeader>
      <StyledContent className="content transactions-content">
        <ErrorBoundary resetOnProps>{children}</ErrorBoundary>
      </StyledContent>
    </div>
  );
};

const Landing = (): JSX.Element => {
  return (
    <LandingContainer>
      {/* TODO: remove the Provider after complete the migration of the Transactions v2 tabs components */}
      <Provider store={store}>
        <Routes>
          <Route index element={<PaymentsContainer />} />
          {/* add micro-app routes here */}
        </Routes>
      </Provider>
      <Outlet />
    </LandingContainer>
  );
};

export default Landing;
