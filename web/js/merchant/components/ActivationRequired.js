import { NavLink } from 'react-router-dom';
import ModalHeader from 'rzp/ui/ModalHeader';

export default ({ onCloseClick, user }) => {
  let modalBody = (
    <div>
      You can only use Razorpay in test mode until your account is activated.{' '}
      <br />
      Please fill and submit the activation form to access live mode.
      <div class="Modal__actions text-right">
        <NavLink to="/activation" onClick={onCloseClick}>
          <button class="btn btn-primary btn-block">
            Fill Activation Form
          </button>
        </NavLink>
      </div>
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
          You cannot switch to live mode as your activation request was not
          accepted by our partner banks. We would not be able support your
          business at this moment. We have sent you an email with details.
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
    } else {
      modalBody = (
        <div>
          You can only use Razorpay in test mode until your account is
          activated.
          <br />
          Your account is Under Review. The process usually takes 2 to 3 working
          days. We will reach out on your contact email for further
          clarifications.
          {modalAction}
        </div>
      );
    }
  }

  return (
    <div>
      <ModalHeader title="Activation Required" onCloseClick={onCloseClick} />
      <div className="modal-body">{modalBody}</div>
    </div>
  );
};
