import { NavLink } from 'react-router-dom';
import ModalHeader from 'common/ui/ModalHeader';
import ShowWhen from 'merchant/components/ShowWhen';
import { activationDuration } from 'merchant/helpers/data';

export default ({ onCloseClick, user }) => {
  let activationName =
    !user.showInstantActivation || !user.instantActivation.isL1Submitted ? 'Activation' : 'KYC';

  let modalTitle;
  if (user.isHardLimitReached) {
    modalTitle = 'Account Under Review';
  } else {
    modalTitle = `${activationName} Required`;
  }

  let modalBody = (
    <div>
      You can only use Razorpay in test mode until your account is activated. <br />
      <ShowWhen additionalCondition={(user) => user.isAllowedEdit('activation')}>
        Please fill and submit the {activationName} Form to access live mode.
        <div class="Modal__actions text-right">
          <NavLink to="/activation" onClick={onCloseClick}>
            <button class="btn btn-primary btn-block">Fill {activationName} Form</button>
          </NavLink>
        </div>
      </ShowWhen>
    </div>
  );

  if (user.isSubmitted || user.isRejected || user.needsClarification) {
    const modalAction = (
      <div class="Modal__actions text-right">
        <button class="btn btn-primary btn-block" onClick={onCloseClick}>
          Okay!
        </button>
      </div>
    );

    if (user.isRejected) {
      modalBody = (
        <div>
          You cannot switch to live mode as your activation request was not accepted by our partner
          banks. We would not be able support your business at this moment. We have sent you an
          email with details.
          {modalAction}
        </div>
      );
    } else if (user.needsClarification) {
      modalBody = (
        <div>
          You cannot switch to live mode as your account isn't activated yet.
          {modalAction}
        </div>
      );
    } else if (user.isHardLimitReached) {
      modalBody = (
        <div>
          Our compliance team is reviewing your submitted KYC documents again. Once the review is
          successfully completed, you will be able to accept payments again from your customers. We
          will reach out to you over the registered email for any clarification during the review,
          and we assure you that the review will be done in less than 48 hours.
          {modalAction}
        </div>
      );
    } else {
      modalBody = (
        <div>
          You can only use Razorpay in test mode until your account is activated.
          <br />
          Your account is Under Review. We will reach out on your contact email for all updates or
          any clarifications that we may require.
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
