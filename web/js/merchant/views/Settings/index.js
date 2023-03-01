import React, { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Route, NavLink, withRouter, Redirect } from 'react-router-dom';
import RTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';
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
import DashboardBanner from 'common/ui/DashboardBanner';
import CSATSurveyBanner from 'merchant/components/Announcements/CSATSurveyBanner';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { isPaymentMethodEnabled } from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const { ACCOUNT_AND_SETTINGS, API_KEYS, WEBHOOKS, REMINDERS } = ROUTES_INFO;

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
// eslint-disable-next-line react/no-unsafe
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

  render() {
    const {
      tracking,
      user,
      mode,
      location: { pathname },
    } = this.props;

    if (user.isAccountAndSettingsRevampEnabled) {
      if (pathname === '/webhooks') {
        return <Redirect to={WEBHOOKS} />;
      }
      if (pathname === '/keys') {
        return <Redirect to={API_KEYS} />;
      }
      if (pathname === '/reminders') {
        return <Redirect to={REMINDERS} />;
      }
      return <Redirect to={ACCOUNT_AND_SETTINGS} />;
    }

    return (
      <>
        <div className="banner-container">
          <DashboardBanner />
          <CSATSurveyBanner user={user} />
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
                  selfServeTrackInitiate({
                    selfServeAction: 'Webhook List Fetched',
                    page: 'Webhooks',
                    screen: 'Settings',
                  });
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

            <ShowWhen
              additionalCondition={(user) =>
                !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Reminders)
              }
            >
              <NavLink to="/reminders" onClick={() => analyticsGoTo('Reminders')}>
                Reminders
              </NavLink>
            </ShowWhen>

            {this.state.isConnectedAppsFound ? (
              <ShowWhen additionalCondition={(user) => user.isAllowedView('applications')}>
                <NavLink to="/applications">Applications</NavLink>
              </ShowWhen>
            ) : null}

            <ShowWhen additionalCondition={(user) => isPaymentMethodEnabled(user, mode)}>
              <NavLink
                to={ROUTES_INFO.PAYMENT_METHODS}
                onClick={() => analyticsGoTo('Payment Methods')}
              >
                Payment Methods
              </NavLink>
            </ShowWhen>
          </header>
          <TestModeBanner />
          <ErrorBoundary resetOnProps>
            <content>
              <Route
                path="/config"
                render={(props) => (
                  <Configuration
                    {...props}
                    isOldFlow
                    showBranding
                    showMissedOrderPaymentLink
                    showFlashCheckout
                    showPaymentSettings
                    showDefaultRefundSpeed
                    showFirc
                    showFeeBearer
                    showInternationalPayments
                    showEmailNotifications
                    showSmsNotifications
                    showWhatsappNotifications
                    showSkipMandatorySummaryPage
                    showAnnouncements
                  />
                )}
              />
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

              <ShowWhenRoute
                additionalCondition={(user) =>
                  !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Reminders)
                }
                path="/reminders"
                component={Reminders}
              />

              {this.state.isConnectedAppsFound ? (
                <Route exact path="/applications" component={Applications} />
              ) : null}

              <Route path={ROUTES_INFO.PAYMENT_METHODS} component={PaymentMethods} />
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
      user: state.session.user,
    }),
    {
      fetchAddWebsiteWorkflowStatus,
    },
  ),
  withRouter,
)(Settings);
