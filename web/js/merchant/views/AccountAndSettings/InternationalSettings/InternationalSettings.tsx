import React, { Suspense } from 'react';
import { connect } from 'react-redux';
import { NavLink, Navigate, Route, Routes } from 'react-router-dom';

import Breadcrumb from 'common/components/Breadcrumb';
import Loader from 'common/components/Loader';
import ErrorBoundary, { Teams } from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/components/TestModeBanner';
import lazy from 'merchant/routes/LazyLoader';
import {
  accountAndSettingsLink,
  ROUTE_MAP,
} from 'merchant/views/AccountAndSettings/constants/constants';
import {
  StyledDivider,
  StyledTabContentContainer,
  StyledHeader,
  StyledTabContainer,
} from 'merchant/views/AccountAndSettings/styled';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import {
  shouldShowFIRCSection,
  isExporterRewardsEnabled,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import DocsLink from 'merchant/components/DocsLink';
import { Box } from 'merchant_common/views/Reports/components';

const Firs = lazy(() => import(/* webpackChunkName: "FIRS" */ './Tabs/FIRS'));
const InternationalPaymentsCodes = lazy(
  () =>
    import(
      /* webpackChunkName: "PurposeCode" */ 'merchant/views/Account/Profile/components/FIRC/FIRCSection'
    ),
);
const ExporterRewards = lazy(
  () =>
    import(
      /* webpackChunkName: "ExporterRewards" */ 'merchant/views/AccountAndSettings/InternationalSettings/ExporterRewards'
    ),
);

const InternationalSettings = ({ user, location: { pathname } }): JSX.Element | null => {
  if (!user.isAccountAndSettingsRevampEnabled) {
    switch (pathname) {
      case ROUTES_INFO.FIRS:
      case ROUTES_INFO.INTERNATIONAL_PAYMENTS_CODES:
      case ROUTES_INFO.EXPORTER_REWARDS:
        return <Navigate to="/profile" replace />;
      default:
        return <Navigate to="/dashboard" replace />;
    }
  }

  const getRefRoute = (routePath: string) => {
    return `${routePath.replace('/international-settings/', '')}/*`;
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
          <ShowWhen additionalCondition={shouldShowFIRCSection}>
            <NavLink to={ROUTES_INFO.FIRS}>Foreign inward remittance statement</NavLink>
            <NavLink to={ROUTES_INFO.INTERNATIONAL_PAYMENTS_CODES}>
              International payments codes
            </NavLink>
            <ShowWhen additionalCondition={isExporterRewardsEnabled}>
              <NavLink to={ROUTES_INFO.EXPORTER_REWARDS}>Exporter rewards</NavLink>
            </ShowWhen>
          </ShowWhen>
          <Box display="inline-grid" justifyContent="end" minWidth="59%">
            <DocsLink isTab />
          </Box>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps team={Teams.CROSS_BORDER}>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <Routes>
                <Route
                  path={getRefRoute(ROUTES_INFO.FIRS)}
                  element={
                    <RouteGuard additionalCondition={shouldShowFIRCSection}>
                      <StyledTabContentContainer className="content">
                        <Firs />
                      </StyledTabContentContainer>
                    </RouteGuard>
                  }
                />
                <Route
                  path={getRefRoute(ROUTES_INFO.INTERNATIONAL_PAYMENTS_CODES)}
                  element={
                    <RouteGuard additionalCondition={shouldShowFIRCSection}>
                      <StyledTabContentContainer className="content">
                        <InternationalPaymentsCodes />
                      </StyledTabContentContainer>
                    </RouteGuard>
                  }
                />
                <Route
                  path={getRefRoute(ROUTES_INFO.EXPORTER_REWARDS)}
                  element={
                    <RouteGuard additionalCondition={isExporterRewardsEnabled}>
                      <ExporterRewards />
                    </RouteGuard>
                  }
                />
              </Routes>
            </StyledDivider>
          </Suspense>
        </ErrorBoundary>
      </div>
    </StyledTabContainer>
  );
};

export default connect((state) => ({
  user: state.session.user,
}))(InternationalSettings);
