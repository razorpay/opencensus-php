import React, { Suspense } from 'react';
import { connect } from 'react-redux';
import { NavLink, Routes, Navigate, Route } from 'react-router-dom';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';
import lazy from 'merchant/routes/LazyLoader';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import Breadcrumb from 'common/components/Breadcrumb';
import { fetchMerchantWebsiteDetails } from 'merchant/reducers/websitecompliance';
import {
  fetchConnectedApplications,
  fetchOauthConnectedApplications,
} from 'merchant/reducers/applications';
import { newRoutes, newAndOldRouteMap } from './constants/constants';
import { WebsiteAndAppSettingsProps, NewRoutes } from './typings';
import {
  StyledDivider,
  StyledTabContentContainer,
  StyledHeader,
  StyledTabContainer,
} from 'merchant/views/AccountAndSettings/styled';
import {
  accountAndSettingsLink,
  ROUTE_MAP,
} from 'merchant/views/AccountAndSettings/constants/constants';
import Loader from 'common/components/Loader';
import {
  isWebsiteDetailsEnabled,
  isWebhookEnabled,
  isApiKeyEnabled,
  isApplicationEnabled,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { Modules } from 'common/constant/enums';
import { useI18Service } from 'common/i18';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';

import {
  WebsiteAppSettingsFields,
  WebsiteAppSettingsTitles,
} from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import { isPolicyWizardV2Enabled } from 'merchant/views/Account/WebsiteAppDetails/utils';
import { useSplitzService } from 'common/splitz';

const APIKeys = lazy(() => import(/* webpackChunkName: "APIKeysTab" */ './Tabs/ApiKeys'));

const WebsiteAppDetails = lazy(
  () =>
    import(
      /* webpackChunkName: "WebsiteAppDetailsTab" */ 'merchant/views/Account/WebsiteAppDetails'
    ),
);

const BusinessWebsiteDetails = lazy(
  () => import(/* webpackChunkName: "WebsiteAppDetailsTab" */ './Tabs/BusinessWebsiteDetails'),
);

const Webhooks = lazy(
  () => import(/* webpackChunkName: "WebhooksTab" */ 'merchant/views/Settings/Webhooks/List'),
);

const Applications = lazy(
  () => import(/* webpackChunkName: "ApplicationsTab" */ 'merchant/views/Settings/Applications'),
);

const WebsiteAndAppSettings = (props: WebsiteAndAppSettingsProps): JSX.Element => {
  const {
    user,
    websiteSectionDetailsData,
    fetchMerchantWebsiteDetails,
    fetchConnectedApplications,
    fetchOauthConnectedApplications,
    location: { pathname },
    applications,
    activationData,
  } = props;
  const { hasConnectedApplications, connectedAppsloading: isConnectedAppsloading } = applications;
  const splitz = useSplitzService();
  const { isConfigTagEnabled } = useI18Service();
  const extraConfig: ExtraConfig = { abExperiments: splitz.abExperiments, isConfigTagEnabled };

  React.useEffect(() => {
    if (!Object.keys(websiteSectionDetailsData.data).length && !websiteSectionDetailsData.error) {
      if (user.isWebsiteComplianceFlowEnabled) fetchMerchantWebsiteDetails();
    }
    if (isApplicationEnabled(user)) {
      // Keeping backward compatibility
      if (user.isRevokeApplicationEnabled) {
        fetchOauthConnectedApplications();
      } else {
        fetchConnectedApplications();
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  if (!user.isAccountAndSettingsRevampEnabled) {
    if (newRoutes.includes(pathname as NewRoutes)) {
      return <Navigate to={newAndOldRouteMap[pathname]} replace />;
    }
    return <Navigate to="/dashboard" replace />;
  }

  const getRefRoute = (routePath: string) => {
    return `${routePath.replace('/website-app-settings/', '')}/*`;
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
              isWebsiteDetailsEnabled({ user, websiteSectionDetailsData, extraConfig })
            }
          >
            <NavLink to={ROUTES_INFO.WEBSITE_APP_SETTINGS}>
              {isPolicyWizardV2Enabled({ user, splitz, activationData: activationData.data })
                ? WebsiteAppSettingsTitles[WebsiteAppSettingsFields.BUSINESS_POLICY_DETAILS]
                : WebsiteAppSettingsTitles[WebsiteAppSettingsFields.WEBSITE_APP_DETAIL]}
            </NavLink>
          </ShowWhen>
          <NavLink to={ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS}>Business website details</NavLink>
          <ShowWhen additionalCondition={(user) => isApiKeyEnabled(user)}>
            <NavLink to={ROUTES_INFO.API_KEYS}>API keys</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => isWebhookEnabled(user)}>
            <NavLink
              to={ROUTES_INFO.WEBHOOKS}
              onClick={() => {
                selfServeTrackInitiate({
                  selfServeAction: 'Webhook List Fetched',
                  page: 'Webhooks',
                  screen: Modules.AccountAndSettings,
                });
              }}
            >
              Webhooks
            </NavLink>
          </ShowWhen>
          <ShowWhen
            additionalCondition={(user) =>
              isApplicationEnabled(user) && (isConnectedAppsloading || hasConnectedApplications)
            }
          >
            <NavLink to={ROUTES_INFO.APPLICATIONS}>Applications</NavLink>
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
                      path={getRefRoute(ROUTES_INFO.WEBSITE_APP_SETTINGS)}
                      element={
                        <RouteGuard
                          additionalCondition={(user) =>
                            isWebsiteDetailsEnabled({
                              user,
                              websiteSectionDetailsData,
                              extraConfig,
                            })
                          }
                        >
                          <WebsiteAppDetails />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.API_KEYS)}
                      element={
                        <RouteGuard additionalCondition={(user) => isApiKeyEnabled(user)}>
                          <APIKeys />
                        </RouteGuard>
                      }
                    />

                    <Route
                      path={getRefRoute(ROUTES_INFO.WEBHOOKS)}
                      element={
                        <RouteGuard additionalCondition={(user) => isWebhookEnabled(user)}>
                          <Webhooks />
                        </RouteGuard>
                      }
                    />

                    <Route
                      path={getRefRoute(ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS)}
                      element={<BusinessWebsiteDetails />}
                    />

                    <Route
                      path={getRefRoute(ROUTES_INFO.APPLICATIONS)}
                      element={
                        <RouteGuard
                          additionalCondition={(user) =>
                            isApplicationEnabled(user) &&
                            (isConnectedAppsloading || hasConnectedApplications)
                          }
                        >
                          <Applications />
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

export default connect(
  (state) => ({
    user: state.session.user,
    websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
    applications: state.applications,
    activationData: state.websiteCompliance.activationData,
  }),
  { fetchMerchantWebsiteDetails, fetchConnectedApplications, fetchOauthConnectedApplications },
)(WebsiteAndAppSettings);
