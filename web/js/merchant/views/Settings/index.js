import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink, withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';

import ShowWhen from 'merchant/components/ShowWhen';

import TestModeBanner from 'merchant/components/TestModeBanner';
import ApiKeys from 'merchant/views/Settings/Keys/List';
import Reminders from 'merchant/views/Settings/Reminders';
import Webhooks from 'merchant/views/Settings/Webhooks/List';
import Applications from 'merchant/views/Settings/Applications/';
import Application from 'merchant/models/Application';
import Configuration from 'merchant/views/Settings/Configuration';
import ApplicationsNew from 'merchant/views/Settings/Applications/new';
import PaymentMethods from 'merchant/views/Settings/PaymentMethods';

import { fetchAddWebsiteWorkflowStatus } from 'merchant/reducers/profile';

@RTracking(() => window.rzpQ.component('Settings'))
@withRouter
@connect(
  (state) => ({
    mode: state.session.mode,
  }),
  {
    fetchAddWebsiteWorkflowStatus,
  },
)
export default class Settings extends Component {
  state = {
    isWebsiteInWorkflow: false,
    isConnectedAppsFound: false,
  };

  componentWillMount() {
    this.props.fetchAddWebsiteWorkflowStatus().then(({ data }) => {
      this.setState({
        isWebsiteInWorkflow: data,
      });
    });
    let application = new Application();
    application.fetchConnected().then((resp) => {
      this.setState({
        isConnectedAppsFound: Boolean(resp?.data?.count),
      });
    });
  }

  onWebsiteAdd = () => {
    this.setState({
      isWebsiteInWorkflow: true,
    });
  };

  componentDidMount() {
    analyticsGoTo('Settings');
  }

  isPaymentMethodEnabled = (user) => {
    return (
      user.activation_status === 'activated' &&
      user.isOrgRZP === true &&
      user.isInstrumentRequestAllowed() &&
      this.props.mode !== 'test'
    );
  };

  render() {
    const { tracking } = this.props;

    return (
      <tabbed-container>
        <header id="settings-header">
          <ShowWhen additionalCondition={(user) => user.isAllowedView('configuration')}>
            <NavLink
              to="/config"
              onClick={() => {
                analyticsGoTo('Configuration');
                tracking.trackEvent(
                  window.rzpQ.onbr().initiated('dash.settings_action', {
                    action: 'View_Configurations',
                  }),
                );
              }}
            >
              Configuration
            </NavLink>
          </ShowWhen>

          <ShowWhen additionalCondition={(user) => user.isAllowedView('webhooks')}>
            <NavLink
              to="/webhooks"
              onClick={() => {
                analyticsGoTo('Webhooks');
                tracking.trackEvent(
                  window.rzpQ.onbr().initiated('dash.settings_action', {
                    action: 'View_Webhook_Tab',
                  }),
                );
              }}
            >
              Webhooks
            </NavLink>
          </ShowWhen>

          <ShowWhen additionalCondition={(user) => user.isAllowedView('api_keys')}>
            <NavLink
              to="/keys"
              onClick={() => {
                analyticsGoTo('API Keys');
                tracking.trackEvent(
                  window.rzpQ.onbr().initiated('dash.settings_action', {
                    action: 'View_API_Key_Tab',
                  }),
                );
              }}
            >
              API Keys
            </NavLink>
          </ShowWhen>

          <NavLink to="/reminders" onClick={() => analyticsGoTo('Reminders')}>
            Reminders
          </NavLink>

          {this.state.isConnectedAppsFound ? (
            <ShowWhen additionalCondition={(user) => user.isAllowedView('applications')}>
              <NavLink to="/applications">Applications</NavLink>
            </ShowWhen>
          ) : null}

          <ShowWhen additionalCondition={(user) => this.isPaymentMethodEnabled(user)}>
            <NavLink to="/payment-methods" onClick={() => analyticsGoTo('Payment Methods')}>
              Payment Methods
            </NavLink>
          </ShowWhen>
        </header>
        <TestModeBanner />
        <content>
          <Route path="/config" component={Configuration} />
          <Route path="/webhooks" component={Webhooks} />
          <Route
            path="/keys"
            component={(props) => (
              <ApiKeys
                {...props}
                onWebsiteAdd={this.onWebsiteAdd}
                isWebsiteInWorkflow={this.state.isWebsiteInWorkflow}
              />
            )}
          />
          <Route path="/reminders" component={Reminders} />

          {this.state.isConnectedAppsFound ? (
            <Route exact path="/applications" component={Applications} />
          ) : null}

          <Route path="/payment-methods" component={PaymentMethods} />
        </content>
      </tabbed-container>
    );
  }
}

const analyticsGoTo = (name) => {
  window.rzpAnalytics({
    eventCategory: 'Dashboard - Settings',
    eventAction: `Go To - ${name}`,
  });
};
