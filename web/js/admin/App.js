import React, { Component } from 'react';
import { Route, matchPath, Switch, Redirect, Link } from 'react-router-dom';
import ModalContainer, { openSlider, closeSlider } from 'common/modal';
import MainNavLink from 'admin/components/MainNavLink';
import ShowWhen from 'admin/components/ShowWhen';
import ErrorBoundary from 'common/ErrorBoundary';

import Profile from 'admin/profile';

import MerchantList from 'admin/merchants/MerchantList';
import Stats from 'admin/stats';
import PlanList from 'admin/plans/List';
import GatewayRulesList from 'admin/gatewayrules/List';
import Entities from 'admin/entities/List';
import ActionsList from 'admin/adminActions/ActionsList';
import EmailLogsList from 'admin/emailLogs/EmailLogsList';
import PublicFeatures from 'admin/publicfeatures/List';

import MerchantEntity from 'admin/merchants/entity/MerchantEntity';
import GenericEntity from 'admin/entities/Entity';

import MerchantActivationForm from 'admin/merchants/entity/MerchantActivationForm';
import MerchantAnalyticStats from 'admin/merchants/entity/MerchantAnalyticStats';

import WorkflowEntity from 'admin/workflows/Entity';
import WorkflowList from 'admin/workflows/List';

import RequestEntity from 'admin/requests/Entity';
import RequestList from 'admin/requests/List';

import GroupList from 'admin/groups/List';
import UserList from 'admin/users/List';
import UserEntity from 'admin/users/Entity';
import OrgsList from 'admin/organizations/List';
import FieldMaps from 'admin/fieldmaps/List';
import RoleList from 'admin/roles/List';
import PermissionsList from 'admin/permissions/List';
import AuditLog from 'admin/auditlog/List';
import OrgEntity from 'admin/organizations/Entity';
import InvitesList from 'admin/invites/List';

export default class App extends Component {
  render() {
    return (
      <div id="app-container">
        <main>
          <ErrorBoundary resetOnProps location={this.location}>
            <Switch location={this.location}>
              <Route
                path="/merchants/:id/activation"
                component={MerchantActivationForm}
              />
              <Route
                path="/merchants/:id/stats"
                component={MerchantAnalyticStats}
              />
              <Route path="/merchants/:id" component={MerchantEntity} />
              <Route path="/merchants" component={MerchantList} />
              <Route path="/stats" component={Stats} />
              <Route path="/pricing-plans" component={PlanList} />
              <Route path="/gateway-rules" component={GatewayRulesList} />
              <Route path="/entities" component={Entities} />
              <Route path="/actions" component={ActionsList} />
              <Route path="/email-logs" component={EmailLogsList} />
              <Route path="/public-features" component={PublicFeatures} />

              <Route path="/workflows/:id" component={WorkflowEntity} />
              <Route path="/workflows" component={WorkflowList} />

              <Route path="/requests/:id" component={RequestEntity} />
              <Route path="/requests" component={RequestList} />
              <Route path="/groups" component={GroupList} />
              <Route path="/users/:id" component={UserEntity} />
              <Route path="/users" component={UserList} />
              <Route path="/orgs/:orgId" component={OrgEntity} />
              <Route path="/orgs" component={OrgsList} />
              <Route path="/fieldmaps/:orgId" component={FieldMaps} />
              <Route path="/roles" component={RoleList} />
              <Route path="/profile" component={Profile} />
              <Route path="/permissions" component={PermissionsList} />
              <Route path="/audit-log" component={AuditLog} />

              <Route path="/entity/:type/:mode/:id" component={GenericEntity} />

              <Route path="/invites" component={InvitesList} />

              <Redirect to="/merchants" />
            </Switch>
          </ErrorBoundary>
        </main>
        <header>
          <div id="profile-icon">
            Pranav Gupta
            <i class="i-arrow-down" />
            <div class="menu">
              <Link to="/profile">
                <i class="i-user" />Profile
              </Link>
              <Link to="/logout">
                <i class="i-logout" />Logout
              </Link>
            </div>
          </div>
        </header>
        <aside>
          <a id="org-logo" href="/admin">
            <img src="https://cdn.razorpay.com/logo_invert.svg" height="28" />
          </a>
          {links.map((linkGroup, i) => (
            <div key={i}>
              {linkGroup.map((l, i) => (
                <MainNavLink
                  key={i}
                  to={l[1]}
                  permission={l[2]}
                  icon={l[3] || 'layers'}
                >
                  {l[0]}
                </MainNavLink>
              ))}
            </div>
          ))}
        </aside>
        <ModalContainer />
      </div>
    );
  }
}

const links = [
  [
    // title, url, permission, icon
    ['Merchants', '/merchants', 'view_all_merchants', 'user-manager'],
    ['Stats', '/stats', 'view_merchant_stats', 'chart-bar'],
    ['Pricing Plans', '/pricing-plans', 'view_pricing_list', 'rupee'],
    ['Gateway Rules', '/gateway-rules', 'view_gateway_rule'],
    ['Entities', '/entities', 'view_all_entity'],
    ['Actions', '/actions', 'view_actions'],
    ['Email Logs', '/email-logs', 'view_email_logs', 'email'],
    [
      'Public Features',
      '/public-features',
      'manage_onboarding_submissions',
      'magic-hat',
    ],
  ],

  // workflow
  [
    ['Workflows', '/workflows', 'view_all_workflow', 'yes'],
    ['Requests', '/requests', 'view_workflow_requests'],
  ],

  // user access management
  [
    ['Invites', '/invites', 'view_merchant_invite', 'user-plus'],
    ['Organisations', '/orgs', 'view_all_org', 'building'],
    ['Users', '/users', 'view_all_admin', 'user'],
    ['Roles', '/roles', 'view_all_role', 'user-support'],
    ['Permissions', '/permissions', 'view_all_permission', 'hold'],
    ['Groups', '/groups', 'view_group', 'group'],
    ['Audit Log', '/audit-log', 'view_auditlog'],
  ],
];
