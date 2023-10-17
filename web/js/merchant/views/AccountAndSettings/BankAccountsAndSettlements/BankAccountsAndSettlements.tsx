import Breadcrumb from 'common/components/Breadcrumb';
import { CenterLoader } from 'common/components/Loader';
import { useI18Service } from 'common/i18';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { useSplitzService } from 'common/splitz';
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
  StyledHeader,
  StyledTabContainer,
  StyledTabContentContainer,
} from 'merchant/views/AccountAndSettings/styled';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import {
  isBankAccountDetailsAllowed,
  isSettlementsAllowed,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import React, { Suspense } from 'react';
import { connect } from 'react-redux';
import { NavLink, Navigate, Route, Routes } from 'react-router-dom';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';

const { BANK_ACCOUNT_DETAILS, SETTLEMENT_DETAILS } = ROUTES_INFO;
const BankAccountDetails = lazy(
  () =>
    import(
      /* webpackChunkName: "BankAccountDetails" */ 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetails'
    ),
);

const BankAccountDetailsV2 = lazy(
  () =>
    import(
      /* webpackChunkName: "BankAccountDetailsV2" */ 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2'
    ),
);

const SettlementDetails = lazy(
  () =>
    import(
      /* webpackChunkName: "SettlementDetails" */ 'merchant/views/Account/Profile/components/SettlementDetails'
    ),
);

const getTabsContent = ({ type, withStyled = true, user }) => {
  const tabs = {
    bank_account: user.isBankAccountUpdateRevampEnabled ? BankAccountDetailsV2 : BankAccountDetails,
    settlement: SettlementDetails,
  };
  const Component = tabs[type];
  return withStyled ? (
    <StyledTabContentContainer className="profile-container content">
      <Component />
    </StyledTabContentContainer>
  ) : (
    <Component />
  );
};

const BankAccountsAndSettlements = ({ user, location: { pathname } }): JSX.Element | null => {
  const { abExperiments } = useSplitzService();
  const { isConfigTagEnabled } = useI18Service();
  const extraConfig: ExtraConfig = { abExperiments, isConfigTagEnabled };
  if (!user.isAccountAndSettingsRevampEnabled) {
    switch (pathname) {
      case BANK_ACCOUNT_DETAILS:
      case SETTLEMENT_DETAILS:
        return <Navigate to="/profile" replace />;
      default:
        return <Navigate to="/dashboard" replace />;
    }
  }

  const getRefRoute = (routePath: string) => {
    return `${routePath.replace('/bank-accounts-settlements/', '')}/*`;
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
          <ShowWhen additionalCondition={() => isBankAccountDetailsAllowed(extraConfig)}>
            <NavLink to={BANK_ACCOUNT_DETAILS}>Bank account details</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={() => isSettlementsAllowed(extraConfig)}>
            <NavLink to={SETTLEMENT_DETAILS}>Settlement details</NavLink>
          </ShowWhen>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<CenterLoader />}>
            <StyledDivider>
              <Routes>
                <Route
                  path={getRefRoute(BANK_ACCOUNT_DETAILS)}
                  element={
                    <RouteGuard
                      additionalCondition={() => isBankAccountDetailsAllowed(extraConfig)}
                    >
                      {getTabsContent({ type: 'bank_account', withStyled: false, user })}
                    </RouteGuard>
                  }
                />

                <Route
                  path={getRefRoute(SETTLEMENT_DETAILS)}
                  element={
                    <RouteGuard additionalCondition={() => isSettlementsAllowed(extraConfig)}>
                      {getTabsContent({ type: 'settlement', user })}
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
}))(BankAccountsAndSettlements);
