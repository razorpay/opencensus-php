import { NavLink } from 'react-router-dom';
import ModalHeader from 'rzp/ui/ModalHeader';

export default ({ onCloseClick }) => {
  return (
    <div>
      <ModalHeader title="Activation Required" onCloseClick={onCloseClick} />
      <div class="modal-body">
        You can only use Razorpay in test mode until your account is activated.{' '}
        <br />
        Please fill and submit the activation form to access live mode.
        <div class="Modal__actions text-right">
          <NavLink to="/activation" onClick={onCloseClick}>
            <button class="btn btn-primary btn-block">
              Fill Activation Form
              <i
                class="i i-arrow-forward pull-right"
                style={{ paddingTop: '4px' }}
              />
            </button>
          </NavLink>
        </div>
      </div>
    </div>
  );
};
