import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import { updateFeatures } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import FeatureOnboarding from 'merchant/containers/FeatureOnboarding/OnBoarding';
import FeatureOnboardingModal from 'merchant/containers/FeatureOnboarding/OnBoardingModal';
import * as ModalActions from 'rzp/modules/modals';

import SubscriptionsList from 'merchant/containers/Subscriptions/List';
import PlansList from 'merchant/containers/Plans/List';

import EmandatePayments from './EmandatePayments/List';

import ActivationBanner from 'merchant/components/ActivationBanner';
import ShowWhen, { ShowWhenRoute } from 'merchant/components/ShowWhen';

import HostedEmanadateBatches from 'merchant/containers/Subscriptions/Batch/List';

const heading =
  'Collect recurring payments from your customers easily with Razorpay Subscription APIs for all possible recurring billing models. Generate more revenue by capturing more subscriptions annually.';

@connect(
  state => {
    return {
      user: state.session.user,
      business_website: state.session.user.business_website,
      mode: state.session.mode,
    };
  },
  { updateFeatures, showNotification, ...ModalActions }
)
export default class SubscriptionsController extends Component {
  constructor(props) {
    super(props);

    this.prefix = '';
    if (props.user.isOrgRZP) {
      this.prefix = 'Razorpay ';
    }

    this.heading = `Collect recurring payments from your customers easily with Razorpay Subscription APIs for all possible recurring billing models. Generate more revenue by capturing more subscriptions annually.`;
  }

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
          message: `${this.prefix}Subscriptions has been enabled!`,
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

  openActivationModal = () => {
    this.props.openModal({
      component: (
        <div className="subscriptions-onboarding-modal">
          <FeatureOnboardingModal
            onClose={this.props.closeModal}
            heading={`${this.prefix}Subscriptions`}
            description={this.heading}
            formType="subscriptions"
            isTestMode={false}
            isPreStepCompleted={() => !!this.props.business_website}
          />
        </div>
      ),
      size: 'large',
    });
  };

  render() {
    let featureEnabled = this.props.user.isSubscriptionsEnabled;

    if (!featureEnabled) {
      return (
        <FeatureOnboarding
          heading={`${this.prefix}Subscriptions`}
          description={this.heading}
          formType="subscriptions"
          isTestMode={this.props.mode === 'test'}
          enableFeatureInTestMode={this.enableFeature}
          isPreStepCompleted={!!this.props.business_website}
        />
      );
    }

    return (
      <div>
        {this.props.mode === 'test' && (
          <ActivationBanner
            productName={`${this.prefix}Subscriptions`}
            productDocs="https://razorpay.com/docs/subscriptions"
            feature="subscriptions"
            symbol="sub"
            onActivate={this.openActivationModal}
          />
        )}
        <tabbed-container>
          <header id="subscriptions-header">
            <NavLink exact to="/subscriptions">
              Subscriptions
            </NavLink>
            <NavLink to="/plans">Plans</NavLink>

            <NavLink to="/emandates">Payments</NavLink>

            <ShowWhen additionalCondition={user => user.isChargeAtWillEnabled}>
              <NavLink exact to="/subscriptions/batchuploads">
                Batch Upload
              </NavLink>
            </ShowWhen>
          </header>
          <TestModeBanner />
          <content>
            <Switch>
              <ShowWhenRoute
                path="/subscriptions/batchuploads"
                component={HostedEmanadateBatches}
                additionalCondition={user => user.isChargeAtWillEnabled}
              />
              <Route path="/subscriptions" component={SubscriptionsList} />
              <Route path="/plans" component={PlansList} />

              <Route path="/emandates" component={EmandatePayments} />
            </Switch>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
