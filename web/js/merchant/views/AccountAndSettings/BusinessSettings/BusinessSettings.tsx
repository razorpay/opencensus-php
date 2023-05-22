import Breadcrumb from 'common/components/Breadcrumb';
import Loader from 'common/components/Loader';
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
  isAccountDetailsEnabled,
  isGstDetailsEnabled,
  isSupportTicketEnabled,
  isTeamManagementAllowed,
  shouldShowTeamInvitations,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import React, { Suspense } from 'react';
import { connect } from 'react-redux';
import { NavLink, Redirect, Route, Switch } from 'react-router-dom';
import { BusinessSettingsProps } from './typings';

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

const GSTDetails = lazy(
  () =>
    import(/* webpackChunkName: "GSTDetails" */ 'merchant/views/Account/Profile/components/GST'),
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
  if (!user.isAccountAndSettingsRevampEnabled) {
    const path = location.pathname;
    if (path === ROUTES_INFO.MANAGE_TEAM_DETAILS) return <Redirect to="/team" />;
    // apart from manage teams, all sections are taken out of profile page
    else return <Redirect to="/profile" />;
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
          <NavLink to={ROUTES_INFO.ACCOUNT_DETAILS}>Account details</NavLink>
          <ShowWhen additionalCondition={(user) => isAccountDetailsEnabled(user)}>
            <NavLink to={ROUTES_INFO.ACTIVATION_DETAILS}>Activation details</NavLink>
          </ShowWhen>
          <NavLink to={ROUTES_INFO.BUSINESS_DETAILS}>Business details</NavLink>
          <ShowWhen additionalCondition={(user) => isGstDetailsEnabled(user)}>
            <NavLink to={ROUTES_INFO.GST_DETAILS}>GST details</NavLink>
          </ShowWhen>

          <NavLink to={ROUTES_INFO.CUSTOMER_SUPPORT_DETAILS}>Customer support details</NavLink>
          <ShowWhen additionalCondition={(user) => isTeamManagementAllowed(user)}>
            <NavLink to={ROUTES_INFO.MANAGE_TEAM_DETAILS}>Manage team</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => shouldShowTeamInvitations(user)}>
            <NavLink to={ROUTES_INFO.TEAM_INVITATIONS}>Invitations</NavLink>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => isSupportTicketEnabled(user)}>
            <NavLink
              to="/business-settings/ticket-support/tickets"
              isActive={() => {
                const businessConversationRegEXP =
                  '^/business-settings/ticket-support/([^/]+)/([^/]+)/([^/]+)/conversation$';
                return !!(
                  location.pathname === '/business-settings/ticket-support/tickets' ||
                  location.pathname.match(businessConversationRegEXP)
                );
              }}
            >
              {user.isMobileSignupCareActive ? `Support History` : `Support Tickets`}
            </NavLink>
          </ShowWhen>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <main>
                <Switch>
                  <Route
                    path={ROUTES_INFO.ACCOUNT_DETAILS}
                    component={user.isContactDetailsRevamp ? AccountDetailsV2 : AccountDetails}
                  />
                  <Route
                    path={ROUTES_INFO.ACTIVATION_DETAILS}
                    component={(props) => (
                      <StyledTabContentContainer className="content">
                        <ActivationDetails {...props} />
                      </StyledTabContentContainer>
                    )}
                  />
                  <Route
                    path={ROUTES_INFO.BUSINESS_DETAILS}
                    component={(props) => (
                      <StyledTabContentContainer className="content">
                        <BusinessDetails {...props} />
                      </StyledTabContentContainer>
                    )}
                  />
                  <Route
                    path={ROUTES_INFO.GST_DETAILS}
                    component={(props) => (
                      <StyledTabContentContainer className="content">
                        <GSTDetails {...props} />
                      </StyledTabContentContainer>
                    )}
                  />
                  <Route
                    path={ROUTES_INFO.CUSTOMER_SUPPORT_DETAILS}
                    component={(props) => (
                      <StyledTabContentContainer className="content">
                        {user.isContactDetailsRevamp ? (
                          <CustomerSupportDetailsV2 {...props} />
                        ) : (
                          <CustomerSupportDetails {...props} />
                        )}
                      </StyledTabContentContainer>
                    )}
                  />
                  <Route
                    path={ROUTES_INFO.MANAGE_TEAM_DETAILS}
                    component={(props) => (
                      <StyledTabContentContainer className="content">
                        <TeamDetails {...props} />
                      </StyledTabContentContainer>
                    )}
                  />
                  <Route
                    path="/business-settings/ticket-support/tickets"
                    component={(props) => (
                      <StyledTabContentContainer className="content">
                        <SupportTickets {...props} />
                      </StyledTabContentContainer>
                    )}
                  />
                  <Route
                    path="/business-settings/ticket-support/:instance/:id/:ticketType/conversation"
                    component={(props) => (
                      <StyledTabContentContainer className="content">
                        <Conversations {...props} />
                      </StyledTabContentContainer>
                    )}
                  />
                  <ShowWhenRoute
                    path={ROUTES_INFO.TEAM_INVITATIONS}
                    component={(props) => (
                      <StyledTabContentContainer className="content">
                        <TeamInvitations {...props} />
                      </StyledTabContentContainer>
                    )}
                    additionalCondition={(user) => shouldShowTeamInvitations(user)}
                  />
                </Switch>
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

export default connect(mapStateToProps, null)(BusinessSettings);
