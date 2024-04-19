import { Button } from '@razorpay/blade/components';
import { NavLink } from 'react-router-dom';

import ModalHeader from 'common/ui/ModalHeader';
import { isMobileDevice } from 'merchant/components/Home/data';

export default ({ onCloseClick, partnerActivationStatus }) => {
  const activationName = 'Partner KYC';

  const modalTitle = `${activationName} Required`;
  const partnerKycURL = isMobileDevice() ? '/partners/onboarding' : '/partners/activation';

  let modalBody = (
    <div>
      You can only use Razorpay in test mode until your account is activated. <br />
      Please fill and submit the {activationName} Form to access live mode.
      <div class="Modal__actions text-right">
        <NavLink to={partnerKycURL} onClick={onCloseClick}>
          <Button variant="primary"> Fill {activationName} Form</Button>
        </NavLink>
      </div>
    </div>
  );

  if (
    ['under_review', 'kyc_qualified_unactivated', 'needs_clarification', 'rejected'].includes(
      partnerActivationStatus,
    )
  ) {
    const modalAction = (
      <div class="Modal__actions text-right">
        <Button variant="primary" onClick={onCloseClick}>
          Okay!
        </Button>
      </div>
    );

    if (partnerActivationStatus === 'rejected') {
      modalBody = (
        <div>
          You cannot switch to live mode as your activation request was not accepted by our partner
          banks. We would not be able support your business at this moment. We have sent you an
          email with details.
          {modalAction}
        </div>
      );
    } else if (partnerActivationStatus === 'needs_clarification') {
      modalBody = (
        <div class="Modal__actions">
          Your KYC details require further clarifications. Update required details within 1 day,
          otherwise your settlements might get paused.
          <NavLink to={partnerKycURL} onClick={onCloseClick}>
            <Button variant="primary" onClick={onCloseClick}>
              Update Details
            </Button>
          </NavLink>
        </div>
      );
    } else {
      modalBody = (
        <div>
          You can only use Razorpay in test mode until your account is activated. Your account is
          Under Review. We will reach out on your contact email for all updates or any
          clarifications that we may require.
          {modalAction}
        </div>
      );
    }
  }

  return (
    <div>
      <ModalHeader title={modalTitle} onCloseClick={onCloseClick} />
      <div className="modal-body">{modalBody}</div>
    </div>
  );
};
