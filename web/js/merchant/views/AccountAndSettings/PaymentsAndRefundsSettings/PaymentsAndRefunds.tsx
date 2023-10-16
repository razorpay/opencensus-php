import React, { useEffect, Suspense } from 'react';
import { Route, NavLink, Routes, Navigate } from 'react-router-dom';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';
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
import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';

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

const PaymentCaptureAndRefund = (props) => (
  <StyledConfiguration {...props} showPaymentSettings showDefaultRefundSpeed />
);

const FeeBearer = (props) => <StyledConfiguration {...props} showFeeBearer />;

const PaymentsAndRefundsSettings = ({
  user,
  fetchFeatureByNameFn: fetchFeatureByName,
  featureStatusConfig: { data: featureData, loading: isFeatureLoading },
  location: { pathname },
}): JSX.Element => {
  const { abExperiments } = useSplitzService();
  const { isConfigTagEnabled } = useI18Service();
  const extraConfig: ExtraConfig = { abExperiments, isConfigTagEnabled };

  useEffect(() => {
    if (!featureData.hasOwnProperty(feature)) {
      fetchFeatureByName({ userId: user.id, feature });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  if (!user.isAccountAndSettingsRevampEnabled) {
    if (newRoutes.includes(pathname)) {
      return <Navigate to={newAndOldRouteMap[pathname]} replace />;
    }
    return <Navigate to="/dashboard" replace />;
  }

  if (isFeatureLoading) {
    return (
      <div className="page-spinner-container">
        <Loader />
      </div>
    );
  }

  const getRefRoute = (routePath: string) => {
    return `${routePath.replace('/payments-and-refunds-settings/', '')}/*`;
  };

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
              label: ROUTE_MAP[pathname],
              link: pathname,
            },
          ]}
        />
        <StyledHeader className="scrollable-tab-header">
          <ShowWhen additionalCondition={(user) => isBalancesEnabled(user, extraConfig)}>
            <NavLink to={ROUTES_INFO.BALANCES}>Balances</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user): boolean => isCreditsEnabled(user, extraConfig)}>
            <NavLink to={ROUTES_INFO.CREDITS}>Credits</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={() => isReminderEnabled(extraConfig)}>
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
          <ShowWhen
            additionalCondition={(): boolean => isPaymentCaptureAndRefundEnabled(extraConfig)}
          >
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
                  <Routes>
                    <Route
                      path={getRefRoute(ROUTES_INFO.BALANCES)}
                      element={
                        <RouteGuard>
                          <BalanceSettings />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.CREDITS)}
                      element={
                        <RouteGuard>
                          <CreditsSettings />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.REMINDERS)}
                      element={
                        <RouteGuard>
                          <ReminderSettings />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.TRANSACTION_LIMITS)}
                      element={
                        <RouteGuard>
                          <TransactionLimits />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.FEE_BEARER)}
                      element={
                        <RouteGuard
                          additionalCondition={(user): boolean =>
                            shouldShowFeeBearerSelfServe({
                              user,
                              allowCFBInternational: featureData[feature],
                            })
                          }
                        >
                          <FeeBearer />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.CAPTURE_AND_REFUND_SETTINGS)}
                      element={
                        <RouteGuard
                          additionalCondition={(): boolean =>
                            isPaymentCaptureAndRefundEnabled(extraConfig)
                          }
                        >
                          <PaymentCaptureAndRefund />
                        </RouteGuard>
                      }
                    />

                    <Route
                      path={getRefRoute(ROUTES_INFO.FAILED_PAYMENTS_RETRY)}
                      element={
                        <RouteGuard>
                          <MissedOrderPaymentLink />
                        </RouteGuard>
                      }
                    />
                  </Routes>
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
