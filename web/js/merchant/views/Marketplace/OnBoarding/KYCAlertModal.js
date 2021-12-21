import { Link } from 'react-router-dom';

import ModalHeader from 'common/ui/ModalHeader';
import rTracking from 'react-tracking';

const KYCAlertModal = ({ user, switchToTestMode, closeModal, tracking }) => {
  if (user.activation_status === 'under_review') {
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
            to={activationFormUrl}
            class="btn btn-primary btn-block"
            onClick={() => {
              tracking.trackEvent(
                window.rzpQ.onbr().initiated('kyc.form_fill', {
                  clickSource: 'Route',
                }),
              );
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
