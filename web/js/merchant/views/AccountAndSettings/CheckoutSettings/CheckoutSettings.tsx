import React, { Suspense } from 'react';
import { Route, NavLink, Navigate, Routes } from 'react-router-dom';
import { useI18Service } from 'common/i18';
import { useSplitzService } from 'common/splitz';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import Breadcrumb from 'common/components/Breadcrumb';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';
import {
  StyledConfiguration,
  StyledDivider,
  StyledTabContentContainer,
  StyledHeader,
  StyledTabContainer,
} from 'merchant/views/AccountAndSettings/styled';
import { connect } from 'react-redux';
import {
  isCheckoutV2SettingsAllowed,
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
import { withRouter } from 'common/deprecated/withRouter';

const TrustedBadge = lazy(
  () => import(/* webpackChunkName: "BankAccountDetails" */ 'merchant/views/Account/TrustedBadge'),
);

const CheckoutSettings = ({ user, location: { pathname } }): JSX.Element | null => {
  const { abExperiments } = useSplitzService();
  const { isConfigTagEnabled } = useI18Service();
  const extraConfig: ExtraConfig = { abExperiments, isConfigTagEnabled };
  if (!user.isAccountAndSettingsRevampEnabled) {
    switch (pathname) {
      case ROUTES_INFO.BRANDING:
      case ROUTES_INFO.FLASH_CHECKOUT:
      case ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE:
        return <Navigate to="/config" replace />;
      case ROUTES_INFO.TRUSTED_BADGE:
        return <Navigate to="/trustedbadge" replace />;
      default:
        return <Navigate to="/dashboard" replace />;
    }
  }

  const getRefRoute = (routePath: string) => {
    return `${routePath.replace('/checkout-settings/', '')}/*`;
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
          <ShowWhen
            additionalCondition={(user) =>
              isConfigurationViewAllowed(user) && isCheckoutV2SettingsAllowed(extraConfig)
            }
          >
            <NavLink to={ROUTES_INFO.CHECKOUT_STYLING}>Checkout Styling</NavLink>
            <NavLink to={ROUTES_INFO.CHECKOUT_FEATURES}>Checkout Features</NavLink>
          </ShowWhen>
          <ShowWhen
            additionalCondition={(user) =>
              isConfigurationViewAllowed(user) && !isCheckoutV2SettingsAllowed(extraConfig)
            }
          >
            <NavLink to={ROUTES_INFO.BRANDING}>Branding</NavLink>
            <ShowWhen additionalCondition={(user) => isFlashCheckoutAllowed(user, extraConfig)}>
              <NavLink to={ROUTES_INFO.FLASH_CHECKOUT}>Flash Checkout</NavLink>
            </ShowWhen>
            <ShowWhen additionalCondition={() => isSkipMandatorySummaryPageAllowed(extraConfig)}>
              <NavLink to={ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE}>Mandate Summary Page</NavLink>
            </ShowWhen>
          </ShowWhen>
          <ShowWhen
            additionalCondition={(user) =>
              isTrustedBadgeAllowed(user, extraConfig) && !isCheckoutV2SettingsAllowed(extraConfig)
            }
          >
            <NavLink to={ROUTES_INFO.TRUSTED_BADGE}>Trusted Badge</NavLink>
          </ShowWhen>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <StyledTabContentContainer className="content">
                <ShowWhen additionalCondition={(user) => isConfigurationViewAllowed(user)}>
                  <Routes>
                    <Route
                      path={getRefRoute(ROUTES_INFO.BRANDING)}
                      element={
                        <RouteGuard
                          additionalCondition={() => !isCheckoutV2SettingsAllowed(extraConfig)}
                          defaultPath={ROUTES_INFO.CHECKOUT_STYLING}
                        >
                          <StyledConfiguration showBranding />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.FLASH_CHECKOUT)}
                      element={
                        <RouteGuard
                          additionalCondition={() => !isCheckoutV2SettingsAllowed(extraConfig)}
                          defaultPath={ROUTES_INFO.CHECKOUT_FEATURES}
                        >
                          <StyledConfiguration showFlashCheckout />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.SKIP_MANDATORY_SUMMARY_PAGE)}
                      element={
                        <RouteGuard
                          additionalCondition={() => !isCheckoutV2SettingsAllowed(extraConfig)}
                          defaultPath={ROUTES_INFO.CHECKOUT_FEATURES}
                        >
                          <StyledConfiguration showSkipMandatorySummaryPage />
                        </RouteGuard>
                      }
                    />
                  </Routes>
                </ShowWhen>
                <ShowWhen additionalCondition={() => isCheckoutV2SettingsAllowed(extraConfig)}>
                  <ShowWhen additionalCondition={(user) => isConfigurationViewAllowed(user)}>
                    <Routes>
                      <Route
                        path={getRefRoute(ROUTES_INFO.CHECKOUT_STYLING)}
                        element={
                          <RouteGuard>
                            <StyledConfiguration showStyling />
                          </RouteGuard>
                        }
                      />
                      <Route
                        path={getRefRoute(ROUTES_INFO.CHECKOUT_FEATURES)}
                        element={
                          <RouteGuard>
                            <StyledConfiguration showFeatures />
                          </RouteGuard>
                        }
                      />
                    </Routes>
                  </ShowWhen>
                  <Routes>
                    <Route
                      path={getRefRoute(ROUTES_INFO.TRUSTED_BADGE)}
                      element={
                        <RouteGuard
                          additionalCondition={() => false}
                          defaultPath={ROUTES_INFO.CHECKOUT_STYLING}
                        >
                          <TrustedBadge />
                        </RouteGuard>
                      }
                    />
                  </Routes>
                </ShowWhen>
                <Routes>
                  <Route
                    path={getRefRoute(ROUTES_INFO.TRUSTED_BADGE)}
                    element={
                      <RouteGuard
                        additionalCondition={(user) => isTrustedBadgeAllowed(user, extraConfig)}
                      >
                        <TrustedBadge />
                      </RouteGuard>
                    }
                  />
                </Routes>
              </StyledTabContentContainer>
            </StyledDivider>
          </Suspense>
        </ErrorBoundary>
      </div>
    </StyledTabContainer>
  );
};

export default withRouter(
  connect((state) => ({
    user: state.session.user,
  }))(CheckoutSettings),
);
