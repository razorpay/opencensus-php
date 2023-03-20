import React, { Suspense } from 'react';
import { Route, NavLink, Switch, Redirect } from 'react-router-dom';
import { connect } from 'react-redux';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import Breadcrumb from 'common/components/Breadcrumb';
import lazy from 'merchant/routes/LazyLoader';
import { BusinessSettingsProps } from './typings';
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
  isGstDetailsEnabled,
  isTeamManagementAllowed,
  isAccountDetailsEnabled,
  isSupportTicketEnabled,
  shouldShowTeamInvitations,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const ContactDetails = lazy(
  () => import(/* webpackChunkName: "ContactDetails" */ './Tabs/ContactDetails'),
);

const AccountDetails = lazy(
  () => import(/* webpackChunkName: "AccountDetails" */ './Tabs/AccountDetails'),
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
          <NavLink to={ROUTES_INFO.CONTACT_DETAILS}>Contact details</NavLink>
          <ShowWhen additionalCondition={(user) => isAccountDetailsEnabled(user)}>
            <NavLink to={ROUTES_INFO.ACCOUNT_DETAILS}>Account details</NavLink>
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
            <NavLink to="/business-settings/ticket-support/tickets">
              {user.isMobileSignupCareActive ? `Support History` : `Support Tickets`}
            </NavLink>
          </ShowWhen>
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            <StyledDivider>
              <StyledTabContentContainer className="content">
                <main>
                  <Switch>
                    <Route path={ROUTES_INFO.CONTACT_DETAILS} component={ContactDetails} />
                    <Route path={ROUTES_INFO.ACCOUNT_DETAILS} component={AccountDetails} />
                    <Route path={ROUTES_INFO.BUSINESS_DETAILS} component={BusinessDetails} />
                    <Route path={ROUTES_INFO.GST_DETAILS} component={GSTDetails} />
                    <Route
                      path={ROUTES_INFO.CUSTOMER_SUPPORT_DETAILS}
                      component={CustomerSupportDetails}
                    />
                    <Route path={ROUTES_INFO.MANAGE_TEAM_DETAILS} component={TeamDetails} />
                    <Route
                      path="/business-settings/ticket-support/tickets"
                      component={SupportTickets}
                    />
                    <Route
                      path="/business-settings/ticket-support/:instance/:id/:ticketType/conversation"
                      component={Conversations}
                    />
                    <ShowWhenRoute
                      path={ROUTES_INFO.TEAM_INVITATIONS}
                      component={TeamInvitations}
                      additionalCondition={(user) => shouldShowTeamInvitations(user)}
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

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps, null)(BusinessSettings);
