import { NavLink } from 'react-router-dom';
import ModalHeader from 'rzp/ui/ModalHeader';

export default ({ onCloseClick }) => {
  return (
    <div>
      <ModalHeader title="Activation Required" />
      <div class="modal-body">
        You can only use Razorpay in test mode until your account is activated.{' '}
        <br />
        Please fill and submit the{' '}
        <NavLink to="/activation" onClick={onCloseClick}>
          <u>activation form.</u>
        </NavLink>
        <div class="Modal__actions text-right">
          <button class="btn btn-primary" onClick={onCloseClick}>
            OK
          </button>
        </div>
      </div>
    </div>
  );
};
