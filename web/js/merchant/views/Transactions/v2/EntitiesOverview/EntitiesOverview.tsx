import React from 'react';
import { Heading } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { Route, Switch, withRouter } from 'react-router-dom';

import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import lazy from 'merchant/routes/LazyLoader';
import EntityAnalytics from 'merchant/views/Transactions/v2/Analytics/EntityAnalytics';
import { EntityOverviewType } from 'merchant/views/Transactions/v2/Analytics/types';
import GoBack from 'merchant/views/Transactions/v2/common/components/GoBack';
import { TransactionsEntityRoute } from 'merchant/views/Transactions/v2/common/constants';
import {
  StyledContent,
  StyledTabHeader,
  StyledTabItem,
} from 'merchant/views/Transactions/v2/common/styled';
import { trackTransactionsTabClick } from 'merchant/views/Transactions/v2/common/tracking';

import { StyledHeading } from './styled';
import { EntitiesOverviewProps } from './types';
import { getHeading } from './utils';

const SuccessRate = lazy(
  () => import(/* webpackChunkName: "SuccessRate" */ 'merchant/views/Transactions/v1/SuccessRate'),
);

const PaymentsContainer = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentsContainer" */ 'merchant/views/Transactions/v2/Payments/components/PaymentsContainer'
    ),
);
const RefundsContainer = lazy(
  () =>
    import(
      /* webpackChunkName: "RefundsContainer" */ 'merchant/views/Transactions/v2/Refunds/components/RefundsContainer'
    ),
);
const DisputesList = lazy(
  () =>
    import(/* webpackChunkName: "DisputesList" */ 'merchant/views/Transactions/v1/Disputes/List'),
);
const BatchRefundsList = lazy(
  () =>
    import(
      /* webpackChunkName: "BatchRefundsList" */ 'merchant/views/Transactions/v1/BatchRefunds/List'
    ),
);
const BatchRefundsUpload = lazy(
  () =>
    import(
      /* webpackChunkName: "BatchRefundsUpload" */ 'merchant/views/Transactions/v1/BatchRefunds/BatchUpload'
    ),
);
const { FAILED_PAYMENTS, DISPUTES, SUCCESS_RATE, REFUNDS, BATCH_REFUNDS, BATCH_REFUNDS_UPLOAD } =
  TransactionsEntityRoute;

const EntitiesOverview = ({ location: { pathname }, mode }: EntitiesOverviewProps): JSX.Element => {
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
  return (
    <div className="tabbed-container">
      <GoBack />
      {shouldShowOverview ? <EntityAnalytics type={entityAnalyticsType} /> : null}
      {shouldShowHeading ? (
        <StyledHeading id="transactions-header">
          <Heading size="large">{getHeading(pathname as TransactionsEntityRoute)}</Heading>
        </StyledHeading>
      ) : (
        <StyledTabHeader id="transactions-header" className="scrollable-tab-header">
          <ShowWhen
            additionalCondition={(usr) =>
              usr.isAllowedView('refunds') &&
              !usr.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds)
            }
          >
            <StyledTabItem
              to={{
                pathname: REFUNDS,
                state: {
                  prevPath: pathname,
                },
              }}
              onClick={trackTransactionsTabClick(REFUNDS)}
              replace
              exact
            >
              Refunds
            </StyledTabItem>
          </ShowWhen>
          <ShowWhen
            additionalCondition={(usr) =>
              usr.isAllowedView('refunds_batch_uploads') &&
              !usr.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds)
            }
          >
            <StyledTabItem
              to={{
                pathname: BATCH_REFUNDS,
                state: {
                  prevPath: pathname,
                },
              }}
              onClick={trackTransactionsTabClick(BATCH_REFUNDS)}
              replace
              isActive={(match, { pathname: path }) =>
                [BATCH_REFUNDS_UPLOAD, BATCH_REFUNDS].includes(path)
              }
            >
              Batch Refunds
            </StyledTabItem>
          </ShowWhen>
        </StyledTabHeader>
      )}
      <StyledContent className="content transactions-content">
        <ErrorBoundary resetOnProps>
          <Switch>
            <Route path={FAILED_PAYMENTS} component={PaymentsContainer} />
            <ShowWhenRoute
              path={BATCH_REFUNDS_UPLOAD}
              component={BatchRefundsUpload}
              additionalCondition={(usr) =>
                !usr.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds)
              }
            />
            <ShowWhenRoute
              path={BATCH_REFUNDS}
              component={BatchRefundsList}
              additionalCondition={(usr) =>
                !usr.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Refunds)
              }
            />
            <Route path={REFUNDS} component={RefundsContainer} />
            <Route path={DISPUTES} component={DisputesList} />
            <ShowWhenRoute
              path={SUCCESS_RATE}
              component={SuccessRate}
              additionalCondition={(currentUser) =>
                mode === 'live' &&
                currentUser.findTag('success_rate') &&
                currentUser.isAllowedView('success_rate')
              }
            />
          </Switch>
        </ErrorBoundary>
      </StyledContent>
    </div>
  );
};

const mapStateToProps = (state) => ({
  mode: state.session.mode,
});

export default withRouter(connect(mapStateToProps)(EntitiesOverview));
