import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import { updateFeatures } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import FeatureOnboarding from 'merchant/containers/FeatureOnboarding/OnBoarding';

import SubscriptionsList from 'merchant/containers/Subscriptions/List';
import PlansList from 'merchant/containers/Plans/List';

@connect(
  state => {
    return {
      user: state.session.user,
      mode: state.session.mode,
    };
  },
  { updateFeatures, showNotification }
)
export default class SubscriptionsController extends Component {
  enableFeature = () => {
    var data = {
      features: {
        subscriptions: 1,
      },
    };

    return this.props
      .updateFeatures(data, this.props.user.current)
      .then(res => {
        this.props.showNotification({
          type: 'success',
          message: 'Razorpay Subscriptions has been enabled!',
        });
        setTimeout(() => location.reload());
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    // TODO: Link feature
    let featureEnabled = false;

    if (!featureEnabled) {
      const heading =
        'Automate you recurring billing with Razorpay Subscriptions and powerful APIs';

      return (
        <FeatureOnboarding
          heading="Razorpay Subscriptions"
          description={heading}
          formType="subscriptions"
          isTestMode={this.props.mode === 'test'}
          enableFeatureInTestMode={this.enableFeature}
        />
      );
    }

    return (
      <tabbed-container>
        <header id="subscriptions-header">
          <NavLink to="/subscriptions">Subscriptions</NavLink>
          <NavLink to="/plans">Plans</NavLink>
        </header>
        <TestModeBanner />
        <content>
          <Switch>
            <Route path="/subscriptions" component={SubscriptionsList} />
            <Route path="/plans" component={PlansList} />
          </Switch>
        </content>
      </tabbed-container>
    );
  }
}
