import React, { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Route, NavLink, withRouter } from 'react-router-dom';
import RTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import ShowWhen from 'merchant/components/ShowWhen';

import TestModeBanner from 'merchant/components/TestModeBanner';
import ApiKeys from 'merchant/views/Settings/Keys/List';
import Reminders from 'merchant/views/Settings/Reminders';
import Webhooks from 'merchant/views/Settings/Webhooks/List';
import Applications from 'merchant/views/Settings/Applications/';
import Application from 'merchant/models/Application';
import Configuration from 'merchant/views/Settings/Configuration';
import PaymentMethods from 'merchant/views/Settings/PaymentMethods';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

import { fetchAddWebsiteWorkflowStatus } from 'merchant/reducers/profile';
import DashboardBanner from '../../../common/ui/DashboardBanner';

const analyticsGoTo = (name) => {
  window.rzpAnalytics?.({
    eventCategory: 'Dashboard - Settings',
    eventAction: `Go To - ${name}`,
  });
  analyticsTrack({
    objectName: name,
    actionName: 'viewed',
    screen: 'settings',
    properties: {
      location: name,
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
  });
};
class Settings extends Component {
  state = {
    isWebsiteInWorkflow: false,
    isConnectedAppsFound: false,
  };

  UNSAFE_componentWillMount() {
    this.props.fetchAddWebsiteWorkflowStatus().then(({ data }) => {
      this.setState({
        isWebsiteInWorkflow: data,
      });
    });
    const application = new Application();
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
      user.isOrgRZP === true && user.isInstrumentRequestAllowed() && this.props.mode !== 'test'
    );
  };

  render() {
    const { tracking } = this.props;

    return (
      <>
        <div className="banner-container">
          <DashboardBanner />
        </div>
        <tabbed-container>
          {/* To make the header scrollable we just need to add this new class to the header component */}
          <header id="settings-header" className="scrollable-tab-header">
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
          <ErrorBoundary resetOnProps>
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
          </ErrorBoundary>
        </tabbed-container>
      </>
    );
  }
}

export default compose(
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('Settings')),
  connect(
    (state) => ({
      mode: state.session.mode,
    }),
    {
      fetchAddWebsiteWorkflowStatus,
    },
  ),
  withRouter,
)(Settings);
