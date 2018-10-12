import React, { Component } from 'react';
import { connect } from 'react-redux';

import ProductsModal from 'merchant/components/ProducsModal';
import TransactionsModal from 'merchant/components/TransactionsHelperModal';

import TestModeCard from './TestMode';
import ActivationStatusCard from './ActivationStatus';
import LiveModeCard from './LiveMode';

@connect(state => ({ ...state.session, config: state.config.config }))
export default class OnboardingCardInstant extends Component {
  constructor(props) {
    super(props);

    this.state = {
      showProducts: false,
      showTransactionsHelper: false,
    };

    this.showProductsModal = this.showProductsModal.bind(this);
    this.hideProductsModal = this.hideProductsModal.bind(this);
    this.showTransactionsModal = this.showTransactionsModal.bind(this);
    this.hideTransactionsModal = this.hideTransactionsModal.bind(this);
  }

  showProductsModal() {
    this.setState({
      showProducts: true,
    });
  }

  hideProductsModal() {
    this.setState({
      showProducts: false,
    });
  }

  showTransactionsModal() {
    this.setState({
      showTransactionsHelper: true,
    });
  }

  hideTransactionsModal() {
    this.setState({
      showTransactionsHelper: false,
    });
  }

  render() {
    const { mode, user, integration } = this.props,
      {
        has_key_access: hasKeyAccess,
        business_website: businessWebsite,
        instantActivation,
        isSubmitted,
        isActivated,
        isRejected,
        needsClarification,
      } = user,
      { showProducts, showTransactionsHelper } = this.state,
      isKLA = !hasKeyAccess && !businessWebsite,
      commonModeCardProps = {
        mode,
        integration,
        hasKeyAccess,
        isKLA,
        showProductsModal: this.showProductsModal,
      },
      activationCardProps = {
        instantActivation,
        isSubmitted,
        needsClarification,
        isActivated,
      };

    return (
      <div className="onboarding-card-instant">
        {showProducts && <ProductsModal onClose={this.hideProductsModal} />}
        {showTransactionsHelper && (
          <TransactionsModal onClose={this.hideTransactionsModal} />
        )}
        <div className="onboarding-card-instant-content">
          <div className="onboarding-steps clearfix">
            <TestModeCard {...commonModeCardProps} />
            <ActivationStatusCard {...activationCardProps} />
            <LiveModeCard
              instantActivation={instantActivation}
              isRejected={user.isRejected}
              isActivated={user.isActivated}
              showTransactionsModal={this.showTransactionsModal}
              {...commonModeCardProps}
            />
          </div>
          <div className="onboarding-illustration-top">
            <img src="/dist/css/assets/onboarding/top_bg.png" />
          </div>
          <div className="onboarding-illustration" />
          <div className="onboarding-illustration-bottom">
            <img src="/dist/css/assets/onboarding/bottom_bg.png" />
          </div>
        </div>
      </div>
    );
  }
}
