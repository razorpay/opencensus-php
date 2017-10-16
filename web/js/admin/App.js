import React, { Component } from 'react';
import { Route, matchPath, withRouter, Switch } from 'react-router-dom';
import ModalContainer, { openSlider, closeSlider } from 'common/modal';
import MainNavLink from 'admin/components/MainNavLink';
import ShowWhen from 'admin/components/ShowWhen';

import MerchantList from 'admin/merchants/MerchantList';
import Stats from 'admin/stats';
import PlanList from 'admin/plans/List';
import GatewayRulesList from 'admin/gatewayrules/List';
import EntityList from 'admin/entities/list';
import EmailLogsList from 'admin/emailLogs/EmailLogsList';

import MerchantEntity from 'admin/merchants/entity/MerchantEntity';

@withRouter
export default class App extends Component {
  componentWillMount() {
    this.setBaseLocation(this.props.location);
  }

  componentWillReceiveProps(props) {
    this.setBaseLocation(props.location);
  }

  render() {
    return (
      <div id="app-container">
        <main>
          {(this.location && (
            <Switch location={this.location}>
              <Route path="/merchants/:id" component={MerchantEntity} />
              <Route path="/merchants" component={MerchantList} />
              <Route path="/stats" component={Stats} />
              <Route path="/pricing-plans" component={PlanList} />
              <Route path="/gateway-rules" component={GatewayRulesList} />
              <Route path="/entities" component={EntityList} />
              <Route path="/email-logs" component={EmailLogsList} />
            </Switch>
          )) ||
            null}
        </main>
        <header />
        <aside>
          <a href="/admin">
            <img src="https://cdn.razorpay.com/logo_invert.svg" width="146" />
          </a>
          <label>Management</label>
          <MainNavLink to="/merchants" permission="view_all_merchants">
            Merchants
          </MainNavLink>
          <MainNavLink to="/stats" permission="view_merchant_stats">
            Merchant Stats
          </MainNavLink>
          <MainNavLink to="/pricing-plans" permission="view_pricing_list">
            Pricing Plans
          </MainNavLink>
          <MainNavLink to="/gateway-rules" permission="view_gateway_rule">
            Gateway Rules
          </MainNavLink>
          <MainNavLink to="/entities" permission="view_all_entity">
            Entities
          </MainNavLink>
          <MainNavLink to="/actions" permission="view_actions">
            Actions
          </MainNavLink>
          <MainNavLink to="/email-logs" permission="view_email_logs">
            Email Logs
          </MainNavLink>
          <ShowWhen permission="view_workflow_requests">
            <label>Workflows</label>
          </ShowWhen>
          <MainNavLink to="/workflows" permission="view_all_workflow">
            Workflows
          </MainNavLink>
          <MainNavLink
            to="/workflows/actions/list"
            permission="view_workflow_requests"
          >
            Requests
          </MainNavLink>
          <ShowWhen permission="view_all_admin">
            <label>User Access Management</label>
          </ShowWhen>
          <MainNavLink to="/invitations/list" permission="view_merchant_invite">
            Invitations
          </MainNavLink>
          <MainNavLink to="/orgs/list" permission="view_all_org">
            Organizations
          </MainNavLink>
          <MainNavLink to="/users/list" permission="view_all_admin">
            Users
          </MainNavLink>
          <MainNavLink to="/roles/list" permission="view_all_role">
            Roles
          </MainNavLink>
          <MainNavLink to="/permissions/list" permission="view_all_permission">
            Permissions
          </MainNavLink>
          <MainNavLink to="/groups/list" permission="view_group">
            Groups
          </MainNavLink>
          <MainNavLink to="/auditlogs/list" permission="view_auditlog">
            Audit Log
          </MainNavLink>
        </aside>
        <ModalContainer />
      </div>
    );
  }

  setBaseLocation(location) {
    for (let route in entityRoutes) {
      var match = matchPath(location.pathname, route);
      if (match) {
        var EntityView = entityRoutes[route];
        return openSlider({
          component: <EntityView id={match.params.id} />,
          closeUrl: location.pathname,
        });
      }
    }
    closeSlider();
    this.location = location;
  }
}

const entityRoutes = {};
