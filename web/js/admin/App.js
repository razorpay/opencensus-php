import React, { Component } from 'react';
import { Route, matchPath, Switch } from 'react-router-dom';
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

import WorkflowList from 'admin/workflows/List';
// import RequestList from 'admin/workflows/RequestList';

export default class App extends Component {
  render() {
    return (
      <div id="app-container">
        <main>
          <Switch location={this.location}>
            <Route path="/merchants/:id" component={MerchantEntity} />
            <Route path="/merchants" component={MerchantList} />
            <Route path="/stats" component={Stats} />
            <Route path="/pricing-plans" component={PlanList} />
            <Route path="/gateway-rules" component={GatewayRulesList} />
            <Route path="/entities" component={EntityList} />
            <Route path="/email-logs" component={EmailLogsList} />

            <Route path="/workflows" component={WorkflowList} />
          </Switch>
        </main>
        <header />
        <aside>
          <a href="/admin">
            <img src="https://cdn.razorpay.com/logo_invert.svg" width="146" />
          </a>
          {links.map((linkGroup, i) => (
            <div key={i}>
              {linkGroup.map((l, i) => (
                <MainNavLink key={i} to={l[1]} permission={l[2]}>
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
    // title, url, permission
    ['Merchants', '/merchants', 'view_all_merchants'],
    ['Stats', '/stats', 'view_merchant_stats'],
    ['Pricing Plans', '/pricing-plans', 'view_pricing_list'],
    ['Gateway Rules', '/gateway-rules', 'view_gateway_rule'],
    ['Entities', '/entities', 'view_all_entity'],
    ['Actions', '/actions', 'view_actions'],
    ['Email Logs', '/email-logs', 'view_email_logs'],
  ],

  // workflow
  [
    ['Workflows', '/workflows', 'view_all_workflow'],
    ['Requests', '/requests', 'view_workflow_requests'],
  ],

  // user access management
  [
    ['Invitations', '/invite', 'view_merchant_invite'],
    ['Organisations', '/orgs', 'view_all_org'],
    ['Users', '/users', 'view_all_admin'],
    ['Roles', '/roles', 'view_all_role'],
    ['Permissions', '/permissions', 'view_all_permission'],
    ['Groups', '/groups', 'view_group'],
    ['Audit Log', '/logs', 'view_auditlog'],
  ],
];
