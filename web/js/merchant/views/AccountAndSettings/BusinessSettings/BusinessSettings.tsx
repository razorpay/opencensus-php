import React, { Suspense } from 'react';
import { connect } from 'react-redux';
import { NavLink, Navigate, Outlet, Route, Routes } from 'react-router-dom';

import Breadcrumb from 'common/components/Breadcrumb';
import Loader from 'common/components/Loader';
import { withRouter } from 'common/deprecated/withRouter';
import { useI18Service } from 'common/i18';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { useSplitzService } from 'common/splitz';
import DashboardBanner from 'common/ui/DashboardBanner';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';
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
  isAccountDetailsEnabled,
  isGstDetailsEnabled,
  isSupportTicketEnabled,
  isTeamManagementAllowed,
  shouldShowTeamInvitations,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';

import { BusinessSettingsProps } from './typings';
import DocsLink from 'merchant/components/DocsLink';

const AccountDetails = lazy(
  () => import(/* webpackChunkName: "AccountDetails" */ './Tabs/AccountDetails/v1'),
);

const AccountDetailsV2 = lazy(
  () => import(/* webpackChunkName: "AccountDetails" */ './Tabs/AccountDetails/v2'),
);

const ActivationDetails = lazy(
  () => import(/* webpackChunkName: "ActivationDetails" */ './Tabs/ActivationDetails'),
);

const BusinessDetails = lazy(
  () => import(/* webpackChunkName: "BusinessDetails" */ './Tabs/BusinessDetails'),
);

const GSTDetailsV2 = lazy(
  () =>
    import(
      /* webpackChunkName: "GSTDetailsV2" */ 'merchant/views/AccountAndSettings/BusinessSettings/Tabs/GSTDetails'
    ),
);

const CustomerSupportDetails = lazy(
  () =>
    import(
      /* webpackChunkName: "CustomerSupportDetails" */ 'merchant/views/Account/Profile/components/SupportDetails'
    ),
);

const CustomerSupportDetailsV2 = lazy(
  () => import(/* webpackChunkName: "CustomerSupportDetailsV2" */ './Tabs/CustomerSupportDetails'),
);

const TeamDetails = lazy(
  () => import(/* webpackChunkName: "TeamDetailsTab" */ 'merchant/views/Account/ManageTeam'),
);

const SupportTickets = lazy(
  () => import(/* webpackChunkName: "SupportTicketsTab" */ './Tabs/SupportTickets'),
);

const Conversations = lazy(
  () =>
    import(
      /* webpackChunkName: "SupportTicketConversations" */ 'merchant/views/TicketSupport/components/Conversations'
    ),
);

const TeamInvitations = lazy(
  () => import(/* webpackChunkName: "TeamInvitations" */ './Tabs/TeamInvitations'),
);

const BusinessSettings = ({ user, location }: BusinessSettingsProps): JSX.Element => {
  const { abExperiments } = useSplitzService();
  const { isConfigTagEnabled } = useI18Service();

  const isHideStyle = location.pathname === ROUTES_INFO.GST_DETAILS;
  const extraConfig: ExtraConfig = { abExperiments, isConfigTagEnabled };
  if (!user.isAccountAndSettingsRevampEnabled) {
    const path = location.pathname;
    if (path === ROUTES_INFO.MANAGE_TEAM_DETAILS) return <Navigate to="/team" replace />;
    // apart from manage teams, all sections are taken out of profile page
    else return <Navigate to="/profile" replace />;
  }

  const getRefRoute = (routePath: string) => {
    return `${routePath.replace('/business-settings/', '')}/*`;
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
              label: ROUTE_MAP[location.pathname],
              link: location.pathname,
            },
          ]}
        />
        <StyledHeader className="scrollable-tab-header">
          <NavLink to={ROUTES_INFO.ACCOUNT_DETAILS}>Account details</NavLink>
          <ShowWhen additionalCondition={(user) => isAccountDetailsEnabled(user)}>
            <NavLink to={ROUTES_INFO.ACTIVATION_DETAILS}>Activation details</NavLink>
          </ShowWhen>
          <NavLink to={ROUTES_INFO.BUSINESS_DETAILS}>Business details</NavLink>
          <ShowWhen additionalCondition={(user) => isGstDetailsEnabled(user, extraConfig)}>
            <NavLink to={ROUTES_INFO.GST_DETAILS}>GST details</NavLink>
          </ShowWhen>

          <ShowWhen
            additionalCondition={() =>
              !extraConfig.isConfigTagEnabled('account.customer_support_details')
            }
          >
            <NavLink to={ROUTES_INFO.CUSTOMER_SUPPORT_DETAILS}>Customer support details</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => isTeamManagementAllowed(user)}>
            <NavLink to={ROUTES_INFO.MANAGE_TEAM_DETAILS}>Manage team</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => shouldShowTeamInvitations(user)}>
            <NavLink to={ROUTES_INFO.TEAM_INVITATIONS}>Invitations</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => isSupportTicketEnabled(user, extraConfig)}>
            <NavLink
              to="/business-settings/ticket-support/tickets"
              // isActive={() => {
              //   const businessConversationRegEXP =
              //     '^/business-settings/ticket-support/([^/]+)/([^/]+)/([^/]+)/conversation$';
              //   return !!(
              //     location.pathname === '/business-settings/ticket-support/tickets' ||
              //     location.pathname.match(businessConversationRegEXP)
              //   );
              // }}
            >
              {user.isMobileSignupCareActive ? `Support History` : `Support Tickets`}
            </NavLink>
          </ShowWhen>
          <DocsLink title="Documentations" isTab shouldUseBladeLink />
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <main>
                <Routes>
                  <Route
                    path={getRefRoute(ROUTES_INFO.ACCOUNT_DETAILS)}
                    element={
                      user.isContactDetailsRevamp ? <AccountDetailsV2 /> : <AccountDetails />
                    }
                  />
                  <Route
                    element={
                      <StyledTabContentContainer
                        className={isHideStyle ? '' : 'content'}
                        hideStyle={isHideStyle}
                      >
                        <Outlet />
                      </StyledTabContentContainer>
                    }
                  >
                    <Route
                      path={getRefRoute(ROUTES_INFO.ACTIVATION_DETAILS)}
                      element={
                        <RouteGuard>
                          <ActivationDetails />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.BUSINESS_DETAILS)}
                      element={
                        <RouteGuard>
                          <BusinessDetails />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.GST_DETAILS)}
                      element={
                        <RouteGuard>
                          <GSTDetailsV2 />
                        </RouteGuard>
                      }
                    />
                    <Route
                      path={getRefRoute(ROUTES_INFO.CUSTOMER_SUPPORT_DETAILS)}
                      element={
                        <RouteGuard>
                          {user.isContactDetailsRevamp ? (
                            <CustomerSupportDetailsV2 />
                          ) : (
                            <CustomerSupportDetails />
                          )}
                        </RouteGuard>
                      }
                    />

                    <Route
                      path={getRefRoute(ROUTES_INFO.MANAGE_TEAM_DETAILS)}
                      element={
                        <RouteGuard>
                          <TeamDetails />
                        </RouteGuard>
                      }
                    />

                    <Route
                      path={getRefRoute(ROUTES_INFO.TEAM_INVITATIONS)}
                      element={
                        <RouteGuard additionalCondition={(user) => shouldShowTeamInvitations(user)}>
                          <TeamInvitations />
                        </RouteGuard>
                      }
                    />

                    <Route path="ticket-support/*">
                      <Route
                        path="tickets/*"
                        element={
                          <RouteGuard>
                            <SupportTickets />
                          </RouteGuard>
                        }
                      />
                      <Route
                        path=":instance/:id/:ticketType/conversation/*"
                        element={
                          <RouteGuard>
                            <Conversations />
                          </RouteGuard>
                        }
                      />
                    </Route>
                  </Route>
                </Routes>
              </main>
            </StyledDivider>
          </Suspense>
        </ErrorBoundary>
      </div>
    </StyledTabContainer>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default withRouter(connect(mapStateToProps, null)(BusinessSettings));
