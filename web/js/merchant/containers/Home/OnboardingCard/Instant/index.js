import React, { Component } from 'react';
import { connect } from 'react-redux';

import { trackhubsContactUpdate } from 'common/utils/googleAnalytics';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import TestModeCard from './TestMode';
import ActivationStatusCard from './ActivationStatus';
import LiveModeCard from './LiveMode';
import ActivationStatusCardOld from './Activationstatus-old';
import RxCard from './RxCard';
import RTracking from 'react-tracking';
import { hasNeoCouponCode } from './RxCa/data';
import { showAcceptPaymentsModal, hideAcceptPaymentsModal } from 'merchant/reducers/home';
import { fetchInternationalProductsStatus } from 'merchant/reducers/config';
import { fetchAddWebsiteWorkflowStatus } from 'merchant/reducers/profile';
import CaInfoContainer from './RxCa/CaInfo';
import PaymentProgressBar from '../PaymentProgressBar';

import {
  trackTestModeCard,
  trackLiveModeCard,
  trackActivationCard,
  trackDotClick,
  trackClose,
} from './ga';

@connect(
  (state) => ({
    ...state.session,
    config: state.config.config,
    windowWidth: state.app.windowWidth,
    internationalProductsStatus: state.config.internationalProductsStatus,
  }),
  {
    showAcceptPaymentsModal,
    hideAcceptPaymentsModal,
    openModal,
    closeModal,
    fetchInternationalProductsStatus,
    fetchAddWebsiteWorkflowStatus,
  },
)
@RTracking((state, props, args) => {
  return window.rzpQ.component('OnboardingCardInstant');
})
export default class OnboardingCardInstant extends Component {
  constructor(props) {
    super(props);

    this.state = {
      contentWidth: null,
      activeStep: 0,
      isWebsiteInWorkflow: false,
      caStatus: null,
    };

    this.showAcceptPaymentsModal = this.showAcceptPaymentsModal.bind(this);
    this.hideAcceptPaymentsModal = this.hideAcceptPaymentsModal.bind(this);
    this.onClose = this.onClose.bind(this);
  }

  updateCAstatus = (status) => {
    this.setState({
      caStatus: status,
    });
  };

  setActiveStep(activeStep = 0) {
    return activeStep !== this.state.activeStep && this.setState({ activeStep });
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
    Promise.all([
      this.props.fetchAddWebsiteWorkflowStatus(),
      this.props.fetchInternationalProductsStatus(),
    ]).then((resp) => {
      const { 0: websiteWorkflow } = resp;
      this.setState({
        isWebsiteInWorkflow: websiteWorkflow.data,
      });
    });
    trackhubsContactUpdate({
      activation_status: this.props.user.activation_status,
    });
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
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
    const { mode, user, integration, internationalProductsStatus, limitBreach } = this.props,
      {
        has_key_access: hasKeyAccess,
        business_website: businessWebsite,
        instantActivation,
        isSubmitted,
        isActivated,
        isRejected,
        isAccepted,
        needsClarification,
        international,
        activated,
        business_type,
        poi_verification_status,
        isUnregisteredBusiness,
        internationalActivationFlow,
        activation_status: activationStatus,
        campaigns,
        locked,
        isAutoKycDone,
        isHardLimitReached,
        merchant,
        kyc_clarification_reasons,
        canSkipPoiValidation,
      } = user,
      { isKLA, isWebsiteInWorkflow, activeStep } = this.state,
      commonModeCardProps = {
        mode,
        integration,
        hasKeyAccess,
        isKLA,
        showProductsModal: this.showProductsModal,
        setActiveStep: this.setActiveStep,
        merchantId: user.current,
        internationalActivationFlow,
        locked,
        user,
      },
      activationCardProps = {
        mode,
        instantActivation,
        isSubmitted,
        needsClarification,
        isActivated,
        isRejected,
        setActiveStep: this.setActiveStep,
        international,
        activated,
        business_type,
        poi_verification_status,
        isUnregisteredBusiness,
        businessWebsite,
        internationalActivationFlow,
        internationalProductsStatus,
        activationStatus,
        isAccepted,
        isWebsiteInWorkflow,
        locked,
        isAutoKycDone,
        isHardLimitReached,
        merchant,
        kyc_clarification_reasons,
        canSkipPoiValidation,
        user,
      };
    const showJuggernautCaFlow = isActivated && hasNeoCouponCode(campaigns);
    const hasAppliedCa = this.props.user.user.settings['clicked_ca_apply_request_done'];
    const showNitroRXCAFlow =
      isActivated &&
      (user.isProjectNitroEnabled || user.isProjectNitroCorporateCard) &&
      hasAppliedCa;

    return (
      <div className="onboarding-card-instant">
        {showJuggernautCaFlow || showNitroRXCAFlow ? (
          <CaInfoContainer
            updateCAstatus={this.updateCAstatus}
            showNitroRXCAFlow={showNitroRXCAFlow}
          />
        ) : (
          <div className="onboarding-card-instant-content" ref={(node) => (this.content = node)}>
            <div className={`onboarding-steps active-step-${activeStep}`}>
              <TestModeCard
                {...commonModeCardProps}
                onActive={() => this.setActiveStep(0)}
                track={trackTestModeCard}
              />
              {user.isInstantActivationEnabled ? (
                <ActivationStatusCard
                  {...activationCardProps}
                  onActive={() => this.setActiveStep(1)}
                  track={trackActivationCard}
                  limitBreach={limitBreach}
                />
              ) : (
                <ActivationStatusCardOld
                  {...activationCardProps}
                  onActive={() => this.setActiveStep(1)}
                  track={trackActivationCard}
                />
              )}

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
            {user.isInstantActivationEnabled ? (
              <PaymentProgressBar user={user} mode={mode} limitBreach={limitBreach} />
            ) : null}

            <div className="onboarding-illustration-top">
              <img src="/dist/css/assets/onboarding/top_bg.png" />
            </div>
            <div className="onboarding-illustration" />
            <div className="onboarding-illustration-bottom">
              <img src="/dist/css/assets/onboarding/bottom_bg.png" />
            </div>
            {isAccepted && integration.paymentsMade && (
              <div className="btn-close cursor-pointer" onClick={this.onClose}>
                &times;
              </div>
            )}
          </div>
        )}
        <div className="onboarding-step-switcher">
          {[0, 1, 2].map((stepNum) => (
            <div
              className={`onboarding-step-switch${activeStep === stepNum ? ' active' : ''}`}
              key={stepNum}
              onClick={() => (trackDotClick(stepNum), this.setActiveStep(stepNum))}
            />
          ))}
        </div>
        <RxCard lsKey={`rx-ca-${user.current}`} />
      </div>
    );
  }
}
