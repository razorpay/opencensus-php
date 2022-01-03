import { NavLink } from 'react-router-dom';
import ModalHeader from 'common/ui/ModalHeader';
import ShowWhen from 'merchant/components/ShowWhen';

export default ({ onCloseClick, user }) => {
  const activationName =
    !user.showInstantActivation || !user.instantActivation.isL1Submitted ? 'Activation' : 'KYC';

  let modalTitle;
  if (user.isHardLimitReached) {
    modalTitle = 'Account Under Review';
  } else {
    modalTitle = `${activationName} Required`;
  }
  const activationUrl = user.isActivationFormFullView ? '/kyc' : '/activation';

  let modalBody = (
    <div>
      You can only use Razorpay in test mode until your account is activated. <br />
      <ShowWhen additionalCondition={(_user) => _user.isAllowedEdit('activation')}>
        {user.isOrgAxis
          ? 'Please reach out to the Axis Bank to get yourself activated'
          : `Please fill and submit the ${activationName} Form to access live mode.`}
        {!user.isOrgAxis ? (
          <div class="Modal__actions text-right">
            <NavLink to={activationUrl} onClick={onCloseClick}>
              <button class="btn btn-primary btn-block">Fill {activationName} Form</button>
            </NavLink>
          </div>
        ) : null}
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
          You cannot switch to live mode as your account isn&apos;t activated yet.
          {modalAction}
        </div>
      );
    } else if (user.isHardLimitReached) {
      modalBody = (
        <div>
          Our compliance team and partner banks carry out routine audits of your KYC documents. We
          might temporarily pause your settlements during this time, but don&apos;t worry, just look
          for clarifications asked by our team on your registered email. Once we receive the
          clarifications, we will resume your settlements. Upon receiving your response, we will be
          able to process the application within 2 days and re enable settlements for you. Please
          note, you can still accept payments from your customers.{' '}
          <a
            href="https://knowledgebase.razorpay.com/support/solutions/articles/11000103841-why-is-my-settle[%E2%80%A6]ld-and-my-account-under-review-after-getting-activated"
            target="_blank"
            rel="noreferrer"
          >
            More details
          </a>
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
