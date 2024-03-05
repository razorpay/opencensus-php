// TODO: Fix the imports, currently out of scope
// @ts-nocheck
import React from 'react';
import { Heading } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { Outlet } from 'react-router-dom';
import { useI18Service } from 'common/i18';
import ErrorBoundary from '@dashboard/shared-ui/ErrorBoundary';
import ShowWhen from 'shell/components/ShowWhen';
import { withRouter } from 'shell/deprecated/withRouter';
import { StyledHeading } from './styled';
import { EntitiesOverviewProps } from './types';
import { getHeading } from './utils';
import EntityAnalytics from 'apps/self-serve/src/App/Transactions/v2/Analytics/EntityAnalytics';
import { EntityOverviewType } from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import GoBack from 'apps/self-serve/src/App/Transactions/v2/common/components/GoBack';
import { TransactionsEntityRoute } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import {
  StyledContent,
  StyledTabHeader,
  StyledTabItem,
} from 'apps/self-serve/src/App/Transactions/v2/common/styled';
import { trackTransactionsTabClick } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';

const { FAILED_PAYMENTS, DISPUTES, SUCCESS_RATE, REFUNDS, BATCH_REFUNDS, BATCH_REFUNDS_UPLOAD } =
  TransactionsEntityRoute;

const EntitiesOverview = ({ location: { pathname } }: EntitiesOverviewProps): JSX.Element => {
  const shouldShowHeading = [FAILED_PAYMENTS, DISPUTES, SUCCESS_RATE].includes(
    pathname as TransactionsEntityRoute,
  );
  const shouldShowOverview = [
    FAILED_PAYMENTS,
    REFUNDS,
    BATCH_REFUNDS,
    BATCH_REFUNDS_UPLOAD,
  ].includes(pathname as TransactionsEntityRoute);
  const entityAnalyticsType =
    pathname === FAILED_PAYMENTS ? EntityOverviewType.Failed : EntityOverviewType.Refunds;
  const { isConfigTagEnabled } = useI18Service();

  return (
    <div className="tabbed-container">
      <GoBack />
      {shouldShowOverview ? <EntityAnalytics type={entityAnalyticsType} /> : null}
      {shouldShowHeading ? (
        <StyledHeading id="transactions-header">
          <Heading size="large">{getHeading(pathname as TransactionsEntityRoute)}</Heading>
        </StyledHeading>
      ) : (
        <StyledTabHeader id="transactions-header">
          <ShowWhen
            additionalCondition={(usr: any) =>
              usr.isAllowedView('refunds') && !isConfigTagEnabled('refunds.refund')
            }
          >
            <StyledTabItem
              to={REFUNDS}
              onClick={trackTransactionsTabClick(REFUNDS)}
              replace
              end
              state={{
                prevPath: pathname,
              }}
            >
              Refunds
            </StyledTabItem>
          </ShowWhen>
          <ShowWhen
            additionalCondition={(usr: any) =>
              usr.isAllowedView('refunds_batch_uploads') && !isConfigTagEnabled('refunds.refund')
            }
          >
            <StyledTabItem
              to={BATCH_REFUNDS}
              state={{
                prevPath: pathname,
              }}
              onClick={trackTransactionsTabClick(BATCH_REFUNDS)}
              replace
              className={[BATCH_REFUNDS_UPLOAD, BATCH_REFUNDS].includes(pathname) ? 'active' : ''}
            >
              Batch Refunds
            </StyledTabItem>
          </ShowWhen>
        </StyledTabHeader>
      )}
      <StyledContent className="content transactions-content">
        <ErrorBoundary resetOnProps>
          <Outlet />
        </ErrorBoundary>
      </StyledContent>
    </div>
  );
};

const mapStateToProps = (state: any) => ({
  mode: state.session.mode,
});

export default withRouter(connect(mapStateToProps)(EntitiesOverview));
