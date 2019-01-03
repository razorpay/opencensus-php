import React, { Component } from 'react';
import { connect } from 'react-redux';

import TestModeCard from './TestMode';
import ActivationStatusCard from './ActivationStatus';
import LiveModeCard from './LiveMode';

import {
  showAcceptPaymentsModal,
  hideAcceptPaymentsModal,
} from 'merchant/modules/home';

import {
  trackTestModeCard,
  trackLiveModeCard,
  trackActivationCard,
  trackDotClick,
  trackClose,
} from './ga';

@connect(
  state => ({
    ...state.session,
    config: state.config.config,
    windowWidth: state.app.windowWidth,
  }),
  {
    showAcceptPaymentsModal,
    hideAcceptPaymentsModal,
  }
)
export default class OnboardingCardInstant extends Component {
  constructor(props) {
    super(props);

    this.state = {
      contentWidth: null,
      activeStep: 0,
    };

    this.showAcceptPaymentsModal = this.showAcceptPaymentsModal.bind(this);
    this.hideAcceptPaymentsModal = this.hideAcceptPaymentsModal.bind(this);
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

  showAcceptPaymentsModal() {
    this.props.showAcceptPaymentsModal();
  }

  hideAcceptPaymentsModal() {
    this.props.hideAcceptPaymentsModal();
  }

  onClose(e) {
    trackClose();

    return this.props.onClose && this.props.onClose(e);
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
        isAccepted,
        needsClarification,
      } = user,
      { showTransactionsHelper, isKLA, contentWidth, activeStep } = this.state,
      commonModeCardProps = {
        mode,
        integration,
        hasKeyAccess,
        isKLA,
        showProductsModal: this.showProductsModal,
        setActiveStep: this.setActiveStep,
        merchantId: user.current,
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
        <div
          className="onboarding-card-instant-content"
          ref={node => (this.content = node)}
        >
          <div className={`onboarding-steps active-step-${activeStep}`}>
            <TestModeCard
              {...commonModeCardProps}
              onActive={() => this.setActiveStep(0)}
              track={trackTestModeCard}
            />
            <ActivationStatusCard
              {...activationCardProps}
              onActive={() => this.setActiveStep(1)}
              track={trackActivationCard}
            />
            <LiveModeCard
              instantActivation={instantActivation}
              isRejected={user.isRejected}
              isActivated={user.isActivated}
              isSubmitted={user.isSubmitted}
              showTransactionsModal={this.showAcceptPaymentsModal}
              onActive={() => this.setActiveStep(2)}
              {...commonModeCardProps}
              track={trackLiveModeCard}
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
              <div className="btn-close cursor-pointer" onClick={this.onClose}>
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
              onClick={() => (
                trackDotClick(stepNum), this.setActiveStep(stepNum)
              )}
            />
          ))}
        </div>
      </div>
    );
  }
}
