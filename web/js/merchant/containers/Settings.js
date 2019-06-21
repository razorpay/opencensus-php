import React, { Component } from 'react';
import { Route, Switch, NavLink, withRouter } from 'react-router-dom';

import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import ApiKeys from 'merchant/containers/Keys/List';
import Reminders from 'merchant/containers/Reminders';
import Webhooks from 'merchant/containers/Webhooks/List';
import Applications from 'merchant/containers/Applications/';
import Configuration from 'merchant/containers/Configuration';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import ApplicationsNew from 'merchant/containers/Applications/new';

@withRouter
export default class Settings extends Component {
  componentDidMount() {
    analyticsGoTo('Settings');
  }

  render() {
    return (
      <tabbed-container>
        <header id="settings-header">
          <ShowWhen
            additionalCondition={user => user.isAllowedView('configuration')}
          >
            <NavLink
              to="/config"
              onClick={() => analyticsGoTo('Configuration')}
            >
              Configuration
            </NavLink>
          </ShowWhen>

          <ShowWhen
            additionalCondition={user => user.isAllowedView('webhooks')}
          >
            <NavLink to="/webhooks" onClick={() => analyticsGoTo('Webhooks')}>
              Webhooks
            </NavLink>
          </ShowWhen>

          <ShowWhen
            additionalCondition={user => user.isAllowedView('api_keys')}
          >
            <NavLink to="/keys" onClick={() => analyticsGoTo('API Keys')}>
              API Keys
            </NavLink>
          </ShowWhen>

          <ShowWhen additionalCondition={user => user.isRemindersEnabled}>
            <NavLink to="/reminders" onClick={() => analyticsGoTo('Reminders')}>
              Reminders
            </NavLink>
          </ShowWhen>

          <ShowWhen
            featureEnabled="Oauth"
            additionalCondition={user => user.isAllowedView('applications')}
          >
            <NavLink to="/applications">Applications</NavLink>
          </ShowWhen>
        </header>
        <TestModeBanner />
        <content>
          <Route path="/config" component={Configuration} />
          <Route path="/webhooks" component={Webhooks} />
          <Route path="/keys" component={ApiKeys} />
          <ShowWhenRoute
            path="/reminders"
            component={Reminders}
            additionalCondition={user => user.isRemindersEnabled}
          />
          <Switch>
            <Route exact path="/applications" component={Applications} />
            <Route exact path="/applications/new" component={ApplicationsNew} />
            <Route path="/applications/:id" component={ApplicationsNew} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}

const analyticsGoTo = name => {
  window.rzpAnalytics({
    eventCategory: 'Dashboard - Settings',
    eventAction: `Go To - ${name}`,
  });
};
