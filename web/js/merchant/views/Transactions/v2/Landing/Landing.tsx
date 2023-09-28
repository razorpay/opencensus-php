import React from 'react';
import { Outlet } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import ShowWhen from 'merchant/components/ShowWhen';
import LandingPageAnalyticsOverview from 'merchant/views/Transactions/v2/Analytics/LandingAnalytics';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';
import {
  StyledContent,
  StyledTabHeader,
  StyledTabItem,
} from 'merchant/views/Transactions/v2/common/styled';
import { trackTransactionsTabClick } from 'merchant/views/Transactions/v2/common/tracking';
import { Page } from 'merchant/views/Transactions/v2/common/types';

const { ORDERS } = Page;
const {
  PAYMENTS: PAYMENTS_ROUTE,
  ORDERS: ORDERS_ROUTE,
  BATCH_PAYMENTS,
  UPLOAD_INVOICES,
  INVOICES,
} = TransactionsEntityRoute;

export const LandingContainer = ({ children }) => {
  return (
    <div className="tabbed-container">
      <LandingPageAnalyticsOverview />
      <StyledTabHeader id="transactions-header" className="scrollable-tab-header">
        <StyledTabItem to={PAYMENTS_ROUTE} onClick={trackTransactionsTabClick(PAYMENTS_ROUTE)} end>
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
        <ErrorBoundary resetOnProps>{children}</ErrorBoundary>
      </StyledContent>
    </div>
  );
};

const Landing = (): JSX.Element => {
  return (
    <LandingContainer>
      <Outlet />
    </LandingContainer>
  );
};

export default Landing;
