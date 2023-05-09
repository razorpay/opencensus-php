import React, { Suspense } from 'react';
import { connect } from 'react-redux';
import { NavLink, Switch, Redirect, Route } from 'react-router-dom';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
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
    location,
    applications,
  } = props;
  const { hasConnectedApplications, connectedAppsloading: isConnectedAppsloading } = applications;

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
    if (newRoutes.includes(location.pathname as NewRoutes)) {
      return <Redirect to={newAndOldRouteMap[location.pathname]} />;
    }
    return <Redirect to="/dashboard" />;
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
          <ShowWhen
            additionalCondition={(user) =>
              isWebsiteDetailsEnabled({ user, websiteSectionDetailsData })
            }
          >
            <NavLink to={ROUTES_INFO.WEBSITE_APP_SETTINGS}>Website/App Detail</NavLink>
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
                  <Switch>
                    <ShowWhenRoute
                      path={ROUTES_INFO.WEBSITE_APP_SETTINGS}
                      component={WebsiteAppDetails}
                      additionalCondition={(user) =>
                        isWebsiteDetailsEnabled({ user, websiteSectionDetailsData })
                      }
                    />
                    <ShowWhenRoute
                      path={ROUTES_INFO.API_KEYS}
                      component={APIKeys}
                      additionalCondition={(user) => isApiKeyEnabled(user)}
                    />
                    <ShowWhenRoute
                      path={ROUTES_INFO.WEBHOOKS}
                      component={Webhooks}
                      additionalCondition={(user) => isWebhookEnabled(user)}
                    />
                    <Route
                      path={ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS}
                      component={BusinessWebsiteDetails}
                    />
                    <ShowWhenRoute
                      path={ROUTES_INFO.APPLICATIONS}
                      component={Applications}
                      additionalCondition={(user) =>
                        isApplicationEnabled(user) &&
                        (isConnectedAppsloading || hasConnectedApplications)
                      }
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

export default connect(
  (state) => ({
    user: state.session.user,
    websiteSectionDetailsData: state.websiteCompliance.websiteSectionDetailsData,
    applications: state.applications,
  }),
  { fetchMerchantWebsiteDetails, fetchConnectedApplications, fetchOauthConnectedApplications },
)(WebsiteAndAppSettings);
