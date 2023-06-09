import { Link } from 'react-router-dom';

import ModalHeader from 'common/ui/ModalHeader';
import rTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { EASY_ONBOARDING } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { redirectToEasyAfter1sec } from 'merchant/components/Activation/ActivationUtils';

const KYCAlertModal = ({ user, switchToTestMode, closeModal, tracking }) => {
  if (
    user.activation_status === 'under_review' ||
    user.activation_status === 'kyc_qualified_unactivated'
  ) {
    return (
      <div class="MarketPlace--KYC-UnderReview-Modal">
        <ModalHeader title="KYC is Under Review" onCloseClick={closeModal} />

        <div class="modal-body">
          Please note that your KYC document is under review. Meanwhile, you can switch to{' '}
          <a onClick={switchToTestMode}>Test Mode</a> to try out the product.
          <div class="Modal__actions">
            <button class="btn btn-primary btn-block" onClick={closeModal}>
              Okay!
            </button>
          </div>
        </div>
      </div>
    );
  }

  const activationFormUrl = user.isActivationFormFullView ? '/kyc' : '/activation';
  const isSignupWithEasyOnboarding = user?.user?.signup_campaign === EASY_ONBOARDING;

  return (
    <div class="MarketPlace--KYC-Required-Modal">
      <ModalHeader title="KYC Document Required" onCloseClick={closeModal} />

      <div class="modal-body">
        Please complete KYC form to start using Route.
        <br />
        Once the form is completed, our team will review it and your account will get activated.
        Meanwhile, you can try it out in <a onClick={switchToTestMode}>Test Mode</a>
        <div class="Modal__actions">
          <Link
            to={!isSignupWithEasyOnboarding ? activationFormUrl : ''}
            class="btn btn-primary btn-block"
            onClick={() => {
              tracking.trackEvent(
                window.rzpQ.onbr().initiated('kyc.form_fill', {
                  clickSource: 'Route',
                }),
              );
              if (isSignupWithEasyOnboarding) {
                analyticsTrack({
                  objectName: 'redirect to easy-dashboard CTA',
                  actionName: 'Redirect',
                  screen: 'onboarding',
                  properties: {
                    'CTA Label': 'Fill KYC Form',
                  },
                });
                redirectToEasyAfter1sec();
              }
              closeModal();
            }}
          >
            Fill KYC Form
          </Link>
        </div>
      </div>
    </div>
  );
};

export default rTracking(() => {
  window.rzpQ.component('WelcomeModal');
})(KYCAlertModal);
