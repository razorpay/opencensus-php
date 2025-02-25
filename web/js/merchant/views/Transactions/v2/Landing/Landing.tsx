import React, { useEffect } from 'react';
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
import { track, trackTransactionsTabClick } from 'merchant/views/Transactions/v2/common/tracking';
import { Page } from 'merchant/views/Transactions/v2/common/types';
import { useI18Service } from 'common/i18';
import { shouldHideAnalytics } from 'merchant/views/Transactions/v2/common/utils';
import { useStore } from '@federated/apps/shell/commonStore';

const { ORDERS } = Page;
const {
  PAYMENTS: PAYMENTS_ROUTE,
  ORDERS: ORDERS_ROUTE,
  BATCH_PAYMENTS,
  UPLOAD_INVOICES,
  INVOICES,
} = TransactionsEntityRoute;

export const LandingContainer = ({ children }) => {
  const { isConfigTagEnabled } = useI18Service();
  const { user, mode } = useStore((state) => ({
    user: state.session.user,
    mode: state.session.mode,
  }));

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

  const isAnalyticsHidden = shouldHideAnalytics(user, mode);

  return (
    <div className="tabbed-container">
      {isAnalyticsHidden ? null : <LandingPageAnalyticsOverview />}
      <StyledTabHeader id="transactions-header">
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

        <ShowWhen additionalCondition={(usr) => usr.isAllowedView(ORDERS) && !usr.isJnKOmniEnabled}>
          <StyledTabItem to={ORDERS_ROUTE} onClick={trackTransactionsTabClick(ORDERS_ROUTE)}>
            Orders
          </StyledTabItem>
        </ShowWhen>

        <ShowWhen
          additionalCondition={(user) =>
            user.international &&
            user.isAllowedView('b2b_payments') &&
            !isConfigTagEnabled('transactions.upload_invoices') &&
            !user.isCountrySingapore
          }
        >
          <StyledTabItem to={UPLOAD_INVOICES} onClick={trackTransactionsTabClick(UPLOAD_INVOICES)}>
            Upload Invoices
          </StyledTabItem>
        </ShowWhen>

        <ShowWhen
          featureEnabled={['opgsp_import_flow', 'enable_jpmc_import_flow']}
          additionalCondition={(usr) =>
            usr.isAllowedView('b2b_payments') && !isConfigTagEnabled('settings.international')
          }
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
