import React, { Suspense } from 'react';
import { Route, NavLink, Redirect } from 'react-router-dom';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import Breadcrumb from 'common/components/Breadcrumb';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import {
  StyledConfiguration,
  StyledDivider,
  StyledTabContentContainer,
  StyledHeader,
  StyledTabContainer,
} from 'merchant/views/AccountAndSettings/styled';
import { connect } from 'react-redux';
import {
  isConfigurationViewAllowed,
  isFlashCheckoutAllowed,
  isSkipMandatorySummaryPageAllowed,
  isTrustedBadgeAllowed,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import {
  accountAndSettingsLink,
  ROUTE_MAP,
} from 'merchant/views/AccountAndSettings/constants/constants';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import Loader from 'common/components/Loader';
import lazy from 'merchant/routes/LazyLoader';

const TrustedBadge = lazy(
  () => import(/* webpackChunkName: "BankAccountDetails" */ 'merchant/views/Account/TrustedBadge'),
);

const CheckoutSettings = ({ user, location: { pathname } }): JSX.Element | null => {
  if (!user.isAccountAndSettingsRevampEnabled) {
    switch (pathname) {
      case ROUTES_INFO.BRANDING:
      case ROUTES_INFO.FLASH_CHECKOUT:
      case ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE:
        return <Redirect to="/config" />;
      case ROUTES_INFO.TRUSTED_BADGE:
        return <Redirect to="/trustedbadge" />;
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
          <ShowWhen additionalCondition={isConfigurationViewAllowed}>
            <NavLink to={ROUTES_INFO.BRANDING}>Branding</NavLink>
            <ShowWhen additionalCondition={isFlashCheckoutAllowed}>
              <NavLink to={ROUTES_INFO.FLASH_CHECKOUT}>Flash Checkout</NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={isSkipMandatorySummaryPageAllowed}>
              <NavLink to={ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE}>Mandate Summary Page</NavLink>
            </ShowWhen>
          </ShowWhen>
          <ShowWhen additionalCondition={isTrustedBadgeAllowed}>
            <NavLink to={ROUTES_INFO.TRUSTED_BADGE}>Trusted Badge</NavLink>
          </ShowWhen>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <StyledTabContentContainer className="content">
                <ShowWhen additionalCondition={isConfigurationViewAllowed}>
                  <Route
                    path={ROUTES_INFO.BRANDING}
                    render={(props) => <StyledConfiguration {...props} showBranding />}
                  />
                  <Route
                    path={ROUTES_INFO.FLASH_CHECKOUT}
                    render={(props) => <StyledConfiguration {...props} showFlashCheckout />}
                  />
                  <Route
                    path={ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE}
                    render={(props) => (
                      <StyledConfiguration {...props} showSkipMandatorySummaryPage />
                    )}
                  />
                </ShowWhen>
                <ShowWhenRoute
                  path={ROUTES_INFO.TRUSTED_BADGE}
                  component={TrustedBadge}
                  additionalCondition={isTrustedBadgeAllowed}
                />
              </StyledTabContentContainer>
            </StyledDivider>
          </Suspense>
        </ErrorBoundary>
      </div>
    </StyledTabContainer>
  );
};

export default connect((state) => ({
  user: state.session.user,
}))(CheckoutSettings);
