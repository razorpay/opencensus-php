import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

import ProductsModal from 'merchant/components/ProducsModal';
import TransactionsModal from 'merchant/components/TransactionsHelperModal';

import TestModeCard from './TestMode';
import ActivationStatusCard from './ActivationStatus';
import LiveModeCard from './LiveMode';

let showProductsModalOnLoad = window.location.href.indexOf('products') > 0;

@withRouter
@connect(state => ({
  ...state.session,
  config: state.config.config,
  windowWidth: state.app.windowWidth,
}))
export default class OnboardingCardInstant extends Component {
  constructor(props) {
    super(props);

    this.state = {
      showProducts: showProductsModalOnLoad,
      showTransactionsHelper: false,
      contentWidth: null,
      activeStep: 0,
    };

    this.onCloseProductsModal = null;
    this.showProductsModal = this.showProductsModal.bind(this);
    this.showTransactionsModal = this.showTransactionsModal.bind(this);
    this.hideTransactionsModal = this.hideTransactionsModal.bind(this);
    this.handleProductsModalBack = this.handleProductsModalBack.bind(this);
    const hideProductsModal = (this.hideProductsModal = this.hideProductsModal.bind(
      this
    ));

    if (showProductsModalOnLoad) {
      this.hideProductsModal = () => {
        hideProductsModal(() => {
          this.props.history.replace('/dashboard');
          this.hideProductsModal = hideProductsModal;
        });
      };
    }
  }

  setActiveStep(activeStep = 0) {
    return (
      activeStep !== this.state.activeStep && this.setState({ activeStep })
    );
  }

  setContentWidth(width) {
    this.setState({
      contentWidth: width,
    });
  }

  componentDidMount() {
    if (this.content) {
      this.setContentWidth(this.content.innerWidth);
    }
  }

  componentWillReceiveProps(nextProps) {
    if (this.content && nextProps.windowWidth !== this.props.windowWidth) {
      this.setContentWidth(this.content.innerWidth);
    }
  }

  showProductsModal(onCloseCb) {
    if (typeof onCloseCb === 'function') {
      this.onCloseProductsModal = onCloseCb;
    }

    this.setState({
      showProducts: true,
    });
  }

  hideProductsModal(onHide) {
    this.setState(
      {
        showProducts: false,
      },
      typeof onHide === 'function' ? onHide : void 0
    );
  }

  handleProductsModalBack() {
    this.hideProductsModal(
      () => (
        this.onCloseProductsModal && this.onCloseProductsModal(),
        (this.onCloseProductsModal = null)
      )
    );
  }

  showTransactionsModal(isKLA) {
    this.setState({
      showTransactionsHelper: true,
      isKLA,
    });
  }

  hideTransactionsModal() {
    this.setState({
      showTransactionsHelper: false,
      isKLA: false,
    });
  }

  render() {
    const { mode, user, integration, onClose } = this.props,
      {
        has_key_access: hasKeyAccess,
        business_website: businessWebsite,
        instantActivation,
        isSubmitted,
        isActivated,
        isRejected,
        isAccepted,
        needsClarification,
      } = user,
      {
        showProducts,
        showTransactionsHelper,
        isKLA,
        contentWidth,
        activeStep,
      } = this.state,
      commonModeCardProps = {
        mode,
        integration,
        hasKeyAccess,
        isKLA,
        showProductsModal: this.showProductsModal,
        setActiveStep: this.setActiveStep,
      },
      activationCardProps = {
        instantActivation,
        isSubmitted,
        needsClarification,
        isActivated,
        isRejected,
        setActiveStep: this.setActiveStep,
      };

    return (
      <div className="onboarding-card-instant">
        {showProducts && (
          <ProductsModal
            onClose={this.hideProductsModal}
            onBack={this.handleProductsModalBack}
          />
        )}
        {showTransactionsHelper && (
          <TransactionsModal
            onClose={this.hideTransactionsModal}
            isKLA={isKLA}
            showProductsModal={this.showProductsModal}
            showTransactionsModal={this.showTransactionsModal}
          />
        )}
        <div
          className="onboarding-card-instant-content"
          ref={node => (this.content = node)}
        >
          <div className={`onboarding-steps active-step-${activeStep}`}>
            <TestModeCard
              {...commonModeCardProps}
              onActive={() => this.setActiveStep(0)}
            />
            <ActivationStatusCard
              {...activationCardProps}
              onActive={() => this.setActiveStep(1)}
            />
            <LiveModeCard
              instantActivation={instantActivation}
              isRejected={user.isRejected}
              isActivated={user.isActivated}
              isSubmitted={user.isSubmitted}
              showTransactionsModal={this.showTransactionsModal}
              onActive={() => this.setActiveStep(2)}
              merchantId={user.current}
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
          {isAccepted &&
            integration.paymentsMade && (
              <div className="btn-close cursor-pointer" onClick={onClose}>
                &times;
              </div>
            )}
        </div>
        <div className="onboarding-step-switcher">
          {[0, 1, 2].map(stepNum => (
            <div
              className={`onboarding-step-switch${
                activeStep === stepNum ? ' active' : ''
              }`}
              key={stepNum}
              onClick={() => this.setActiveStep(stepNum)}
            />
          ))}
        </div>
      </div>
    );
  }
}
