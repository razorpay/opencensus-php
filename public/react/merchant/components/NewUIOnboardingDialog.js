import { NavLink } from 'react-router-dom';
import ModalHeader from 'rzp/ui/ModalHeader';

export default ({ onShowChanges, onCancelClick }) => {
  return (
    <div>
      <ModalHeader title="We have made some changes..." />
      <div class="modal-body">
        To simplify your experience, we have made some small changes on the dashboard
        <div class="Modal__actions clearfix text-center">
          <button
            class="btn btn-default pull-left"
            onClick={onCancelClick}
            style={{ width: '48%' }}
          >
            Skip
          </button>

          <button
            class="btn btn-primary pull-right"
            onClick={onShowChanges}
            style={{ width: '48%' }}
          >
            Show Changes
          </button>
        </div>
      </div>
    </div>
  );
};
