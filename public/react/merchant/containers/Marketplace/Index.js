import { connect } from 'react-redux';
import React, { Component } from 'react';
import { Route, Switch, NavLink } from 'react-router-dom';

import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

import AccountsList from 'merchant/containers/Marketplace/Accounts/List';
import ActivationBanner from 'merchant/components/ActivationBanner';
import FeatureOnboarding from 'merchant/containers/FeatureOnboarding/OnBoarding';
import FeatureOnboardingModal from 'merchant/containers/FeatureOnboarding/OnBoardingModal';
import PaymentsList from 'merchant/containers/Marketplace/Payments/List';
import ReversalsList from 'merchant/containers/Marketplace/Reversals/List';
import TestModeBanner from 'merchant/containers/TestModeBanner';
import TransfersList from 'merchant/containers/Marketplace/Transfers/List';
import { updateFeatures } from 'merchant/modules/config';

const heading =
  'Automate your payment transfers for Marketplaces, Vendor, payouts, Regional splits, etc. and manage the complete payment cycle with Razorpay Route.';

@connect(
  state => {
    return {
      user: state.session.user,
      mode: state.session.mode,
    };
  },
  { updateFeatures, showNotification, ...ModalActions }
)
export default class MarketplaceContainer extends Component {
  enableFeature = () => {
    var data = {
      features: {
        marketplace: 1,
      },
    };

    return this.props
      .updateFeatures(data, this.props.user.current)
      .then(res => {
        this.props.showNotification({
          type: 'success',
          message: 'Razorpay Route has been enabled!',
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
        <FeatureOnboardingModal
          onClose={this.props.closeModal}
          heading="Razorpay Route"
          description={heading}
          formType="marketplace"
          isTestMode={false}
        />
      ),
      size: 'large',
    });
  };

  render() {
    let featureEnabled = this.props.user.isMarketplaceEnabled;

    if (!featureEnabled) {
      return (
        <FeatureOnboarding
          heading="Razorpay Route"
          description={heading}
          formType="marketplace"
          isTestMode={this.props.mode === 'test'}
          enableFeatureInTestMode={this.enableFeature}
        />
      );
    }

    return (
      <div>
        {this.props.mode === 'test' &&
          <ActivationBanner
            productName="Razorpay Route"
            productDocs="https://razorpay.com/docs/route"
            feature="marketplace"
            symbol={require('styles/assets/symbols/route.svg')}
            onActivate={this.openActivationModal}
          />}
        <tabbed-container>
          <header id="marketplace-header">
            <NavLink to="/route/payments">Payments</NavLink>
            <NavLink to="/route/transfers">Transfers</NavLink>
            <NavLink to="/route/reversals">Reversals</NavLink>
            <NavLink to="/route/accounts">Accounts</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <Switch>
              <Route path="/route/payments" component={PaymentsList} />
              <Route path="/route/transfers" component={TransfersList} />
              <Route path="/route/reversals" component={ReversalsList} />
              <Route path="/route/accounts" component={AccountsList} />
            </Switch>
          </content>
        </tabbed-container>
      </div>
    );
  }
}
