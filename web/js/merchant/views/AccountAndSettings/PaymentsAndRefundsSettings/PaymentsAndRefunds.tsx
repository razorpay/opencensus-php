import React, { useEffect, Suspense } from 'react';
import { Route, NavLink, Switch, Redirect } from 'react-router-dom';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import lazy from 'merchant/routes/LazyLoader';
import Breadcrumb from 'common/components/Breadcrumb';
import {
  StyledDivider,
  StyledTabContentContainer,
  StyledConfiguration,
  StyledHeader,
  StyledTabContainer,
} from 'merchant/views/AccountAndSettings/styled';
import TransactionLimits from './Tabs/TransactionLimits';
import { fetchFeatureByName as fetchFeatureByNameFn } from 'merchant/reducers/config';
import Loader from 'common/components/Loader';
import {
  accountAndSettingsLink,
  ROUTE_MAP,
} from 'merchant/views/AccountAndSettings/constants/constants';
import MissedOrderPaymentLink from 'merchant/views/Settings/Configuration/MissedOrderPaymentLink';
import {
  shouldShowFeeBearerSelfServe,
  isFailedPaymentRetryEnabled,
  isPaymentCaptureAndRefundEnabled,
  isReminderEnabled,
  isCreditsEnabled,
  isBalancesEnabled,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import {
  newAndOldRouteMap,
  newRoutes,
} from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/constants/constants';
const feature = 'allow_cfb_international';

const BalanceSettings = lazy(
  () => import(/* webpackChunkName: "BalanceSettings" */ 'merchant/views/Account/Balances'),
);

const CreditsSettings = lazy(
  () => import(/* webpackChunkName: "CreditsSettings" */ 'merchant/views/Account/Credits/List'),
);

const ReminderSettings = lazy(
  () => import(/* webpackChunkName: "ReminderSettings" */ 'merchant/views/Settings/Reminders'),
);

const PaymentsAndRefundsSettings = ({
  user,
  fetchFeatureByNameFn: fetchFeatureByName,
  featureStatusConfig: { data: featureData, loading: isFeatureLoading },
  location,
}): JSX.Element => {
  useEffect(() => {
    if (!featureData.hasOwnProperty(feature)) {
      fetchFeatureByName({ userId: user.id, feature });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const PaymentCaptureAndRefund = (props) => (
    <StyledConfiguration {...props} showPaymentSettings showDefaultRefundSpeed />
  );

  const FeeBearer = (props) => <StyledConfiguration {...props} showFeeBearer />;

  if (!user.isAccountAndSettingsRevampEnabled) {
    if (newRoutes.includes(location.pathname)) {
      return <Redirect to={newAndOldRouteMap[location.pathname]} />;
    }
    return <Redirect to="/dashboard" />;
  }

  if (isFeatureLoading) {
    return (
      <div className="page-spinner-container">
        <Loader />
      </div>
    );
  }

  return (
    <StyledTabContainer>
      <div className="banner-container">
        <DashboardBanner />
      </div>
      <div className="tabbed-container">
        <Breadcrumb
          items={[
            accountAndSettingsLink,
            {
              label: ROUTE_MAP[location.pathname],
              link: location.pathname,
            },
          ]}
        />
        <StyledHeader className="scrollable-tab-header">
          <ShowWhen additionalCondition={(user) => isBalancesEnabled(user)}>
            <NavLink to={ROUTES_INFO.BALANCES}>Balances</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user): boolean => isCreditsEnabled(user)}>
            <NavLink to={ROUTES_INFO.CREDITS}>Credits</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => isReminderEnabled(user)}>
            <NavLink to={ROUTES_INFO.REMINDERS}>Reminders</NavLink>
          </ShowWhen>
          <NavLink to={ROUTES_INFO.TRANSACTION_LIMITS}>Transaction limits</NavLink>
          <ShowWhen
            additionalCondition={(user): boolean =>
              shouldShowFeeBearerSelfServe({ user, allowCFBInternational: featureData[feature] })
            }
          >
            <NavLink to={ROUTES_INFO.FEE_BEARER}>Fee bearer</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user): boolean => isPaymentCaptureAndRefundEnabled(user)}>
            <NavLink to={ROUTES_INFO.CAPTURE_AND_REFUND_SETTINGS}>
              Capture and refund settings
            </NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user): boolean => isFailedPaymentRetryEnabled(user)}>
            <NavLink to={ROUTES_INFO.FAILED_PAYMENTS_RETRY}>Failed payments recovery</NavLink>
          </ShowWhen>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <StyledTabContentContainer className="content">
                <main>
                  <Switch>
                    <Route path={ROUTES_INFO.BALANCES} component={BalanceSettings} />
                    <Route path={ROUTES_INFO.CREDITS} component={CreditsSettings} />
                    <Route path={ROUTES_INFO.REMINDERS} component={ReminderSettings} />
                    <Route path={ROUTES_INFO.TRANSACTION_LIMITS} component={TransactionLimits} />
                    <ShowWhenRoute
                      path={ROUTES_INFO.FEE_BEARER}
                      component={FeeBearer}
                      additionalCondition={(user): boolean =>
                        shouldShowFeeBearerSelfServe({
                          user,
                          allowCFBInternational: featureData[feature],
                        })
                      }
                    />
                    <ShowWhenRoute
                      path={ROUTES_INFO.CAPTURE_AND_REFUND_SETTINGS}
                      component={PaymentCaptureAndRefund}
                      additionalCondition={(user): boolean =>
                        isPaymentCaptureAndRefundEnabled(user)
                      }
                    />
                    <Route
                      path={ROUTES_INFO.FAILED_PAYMENTS_RETRY}
                      component={MissedOrderPaymentLink}
                    />
                  </Switch>
                </main>
              </StyledTabContentContainer>
            </StyledDivider>
          </Suspense>
        </ErrorBoundary>
      </div>
    </StyledTabContainer>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    featureStatusConfig: state.config.featureStatusConfig,
  };
};

const mapDispatchToProps = (dispatch) => bindActionCreators({ fetchFeatureByNameFn }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(PaymentsAndRefundsSettings);
