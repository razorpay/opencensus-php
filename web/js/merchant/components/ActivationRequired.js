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

  if (user.isRejected || user.needsClarification) {
    const modalAction = (
      <div class="Modal__actions text-right">
        <button class="btn btn-primary btn-block" onClick={onCloseClick}>
          Ok. Got it!
        </button>
      </div>
    );

    if (user.isRejected) {
      modalBody = (
        <div>
          You cannot switch to live mode as your activation form got rejected
          and we cannot support your business at this moment.
          {modalAction}
        </div>
      );
    } else if (user.needsClarification) {
      modalBody = (
        <div>
          You cannot switch to live mode as we require some clarification from
          your end.
          <br />
          We will send you an email for the same shortly.
          <br />
          Please check your registered email and take necessary action.
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
