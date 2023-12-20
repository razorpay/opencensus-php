import React, { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import ImgBottomBg from 'assets/onboarding/bottom_bg.png';
import ImgTopBg from 'assets/onboarding/top_bg.png';
import Image from 'common/ui/Image';
import { trackhubsContactUpdate } from 'common/utils/googleAnalytics';
import { fetchInternationalProductsStatus } from 'merchant/reducers/config';
import { showAcceptPaymentsModal, hideAcceptPaymentsModal } from 'merchant/reducers/home';
import { fetchAddWebsiteWorkflowStatus } from 'merchant/reducers/profile';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import OnboardingPreview from 'assets/onboarding.svg';

import ActivationStatusCard from './ActivationStatus';
import ActivationStatusCardOld from './Activationstatus-old';
import LiveModeCard from './LiveMode';
import CaInfoContainer from './RxCa/CaInfo';
import { hasNeoCouponCode } from './RxCa/data';
import RxCard from './RxCard';
import TestModeCard from './TestMode';
import {
  trackTestModeCard,
  trackLiveModeCard,
  trackActivationCard,
  trackDotClick,
  trackClose,
} from './ga';

// eslint-disable-next-line react/no-unsafe
@connect(
  (state) => ({
    ...state.session,
    config: state.config.config,
    windowWidth: state.app.windowWidth,
    internationalProductsStatus: state.config.internationalProductsStatus,
    isNcEligibile: state.home.isNcEligibile,
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
@RTracking(() => {
  return window.rzpQ.component('OnboardingCardInstant');
})
export default class OnboardingCardInstant extends Component {
  constructor(props) {
    super(props);

    this.state = {
      // eslint-disable-next-line react/no-unused-state
      contentWidth: null,
      activeStep: 0,
      isWebsiteInWorkflow: false,
      // eslint-disable-next-line react/no-unused-state
      caStatus: null,
    };

    this.showAcceptPaymentsModal = this.showAcceptPaymentsModal.bind(this);
    this.hideAcceptPaymentsModal = this.hideAcceptPaymentsModal.bind(this);
    this.onClose = this.onClose.bind(this);
  }

  updateCAstatus = (status) => {
    this.setState({
      // eslint-disable-next-line react/no-unused-state
      caStatus: status,
    });
  };

  setActiveStep(activeStep = 0) {
    return activeStep !== this.state.activeStep && this.setState({ activeStep });
  }

  setContentWidth(width) {
    this.setState({
      // eslint-disable-next-line react/no-unused-state
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
    const { mode, user, integration, internationalProductsStatus, limitBreach, isNcEligibile } =
      this.props;
    const {
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
    } = user;
    const { isKLA, isWebsiteInWorkflow, activeStep } = this.state;
    const commonModeCardProps = {
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
    };
    const activationCardProps = {
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
    const hasAppliedCa = this.props.user?.user?.settings?.clicked_ca_apply_request_done;
    const showNitroRXCAFlow = isActivated && user.isProjectNitroEnabled && hasAppliedCa;

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
                isNcEligibile={isNcEligibile}
              />
            </div>

            <div className="onboarding-illustration-top">
              <Image src={ImgTopBg} alt="Top" isWebP />
            </div>
            <div class="onboarding-illustration">
              <img
                src={OnboardingPreview}
                alt="onboarding"
                // eslint-disable-next-line react/no-unknown-property
                fetchpriority="high"
              />
            </div>
            <div className="onboarding-illustration-bottom">
              <Image src={ImgBottomBg} alt="Bottom" isWebP />
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
              // eslint-disable-next-line no-sequences
              onClick={() => (trackDotClick(stepNum), this.setActiveStep(stepNum))}
            />
          ))}
        </div>
        <RxCard lsKey={`rx-ca-${user.current}`} />
      </div>
    );
  }
}
