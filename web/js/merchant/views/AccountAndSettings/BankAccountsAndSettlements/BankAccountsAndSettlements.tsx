import React, { Suspense } from 'react';
import { NavLink, Switch, Redirect } from 'react-router-dom';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import Breadcrumb from 'common/components/Breadcrumb';
import { connect } from 'react-redux';
import {
  accountAndSettingsLink,
  ROUTE_MAP,
} from 'merchant/views/AccountAndSettings/constants/constants';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import { StyledDivider, StyledTabContentContainer } from 'merchant/views/AccountAndSettings/styled';
import Loader from 'common/components/Loader';
import lazy from 'merchant/routes/LazyLoader';
import {
  isBankAccountDetailsAllowed,
  isSettlementsAllowed,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';

const { BANK_ACCOUNT_DETAILS, SETTLEMENT_DETAILS } = ROUTES_INFO;

const BankAccountDetails = lazy(() =>
  import(
    /* webpackChunkName: "BankAccountDetails" */ 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetails'
  ),
);
const SettlementDetails = lazy(() =>
  import(
    /* webpackChunkName: "SettlementDetails" */ 'merchant/views/Account/Profile/components/SettlementDetails'
  ),
);

const BankAccountsAndSettlements = ({ user, location: { pathname } }): JSX.Element | null => {
  if (!user.isAccountAndSettingsRevampEnabled) {
    switch (pathname) {
      case BANK_ACCOUNT_DETAILS:
      case SETTLEMENT_DETAILS:
        return <Redirect to="/profile" />;
      default:
        return <Redirect to="/dashboard" />;
    }
  }

  return (
    <>
      <div className="banner-container">
        <DashboardBanner />
      </div>
      <div className="tabbed-container">
        <header className="scrollable-tab-header">
          <Breadcrumb
            items={[
              accountAndSettingsLink,
              {
                label: ROUTE_MAP[pathname],
                link: pathname,
              },
            ]}
          />
          <ShowWhen additionalCondition={isBankAccountDetailsAllowed}>
            <NavLink to={BANK_ACCOUNT_DETAILS}>Bank account details</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={isSettlementsAllowed}>
            <NavLink to={SETTLEMENT_DETAILS}>Settlement details</NavLink>
          </ShowWhen>
        </header>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <StyledTabContentContainer className="profile-container content">
                <Switch>
                  <ShowWhenRoute
                    additionalCondition={isBankAccountDetailsAllowed}
                    path={BANK_ACCOUNT_DETAILS}
                    component={BankAccountDetails}
                  />
                  <ShowWhenRoute
                    additionalCondition={isSettlementsAllowed}
                    path={SETTLEMENT_DETAILS}
                    component={SettlementDetails}
                  />
                </Switch>
              </StyledTabContentContainer>
            </StyledDivider>
          </Suspense>
        </ErrorBoundary>
      </div>
    </>
  );
};

export default connect((state) => ({
  user: state.session.user,
}))(BankAccountsAndSettlements);
