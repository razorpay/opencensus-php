import Breadcrumb from 'common/components/Breadcrumb';
import { CenterLoader } from 'common/components/Loader';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
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
  shouldShowFIRCSection,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import React, { Suspense } from 'react';
import { connect } from 'react-redux';
import { NavLink, Redirect, Switch } from 'react-router-dom';

const { BANK_ACCOUNT_DETAILS, SETTLEMENT_DETAILS, FIRS } = ROUTES_INFO;
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

const FIRCSection = lazy(
  () =>
    import(
      /* webpackChunkName: "FIRCSection" */ 'merchant/views/Account/Profile/components/FIRC/FIRCSection'
    ),
);

const getTabsContent = ({ type, withStyled = true, user }) => {
  const tabs = {
    bank_account: user.isBankAccountUpdateRevampEnabled ? BankAccountDetailsV2 : BankAccountDetails,
    settlement: SettlementDetails,
    firc: FIRCSection,
  };
  const Component = tabs[type];
  return withStyled
    ? (props) => (
        <StyledTabContentContainer className="profile-container content">
          <Component {...props} />
        </StyledTabContentContainer>
      )
    : Component;
};

const BankAccountsAndSettlements = ({ user, location: { pathname } }): JSX.Element | null => {
  if (!user.isAccountAndSettingsRevampEnabled) {
    switch (pathname) {
      case BANK_ACCOUNT_DETAILS:
      case SETTLEMENT_DETAILS:
      case FIRS:
        return <Redirect to="/profile" />;
      default:
        return <Redirect to="/dashboard" />;
    }
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
              label: ROUTE_MAP[pathname],
              link: pathname,
            },
          ]}
        />
        <StyledHeader className="scrollable-tab-header">
          <ShowWhen additionalCondition={isBankAccountDetailsAllowed}>
            <NavLink to={BANK_ACCOUNT_DETAILS}>Bank account details</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={isSettlementsAllowed}>
            <NavLink to={SETTLEMENT_DETAILS}>Settlement details</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={shouldShowFIRCSection}>
            <NavLink to={FIRS}>Forward inwards remittance statement</NavLink>
          </ShowWhen>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<CenterLoader />}>
            <StyledDivider>
              <Switch>
                <ShowWhenRoute
                  additionalCondition={isBankAccountDetailsAllowed}
                  path={BANK_ACCOUNT_DETAILS}
                  component={getTabsContent({ type: 'bank_account', withStyled: false, user })}
                />
                <ShowWhenRoute
                  additionalCondition={isSettlementsAllowed}
                  path={SETTLEMENT_DETAILS}
                  component={getTabsContent({ type: 'settlement', user })}
                />
                <ShowWhenRoute
                  additionalCondition={shouldShowFIRCSection}
                  path={FIRS}
                  component={getTabsContent({ type: 'firc', user })}
                />
              </Switch>
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
