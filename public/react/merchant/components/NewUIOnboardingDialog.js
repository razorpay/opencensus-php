import { NavLink } from 'react-router-dom';
import ModalHeader from 'rzp/ui/ModalHeader';

export default ({ onShowChanges }) => {
  return (
    <div>
      <ModalHeader title="We have made some changes..." />
      <div class="modal-body">
        To simplify your experience, we have made some small changes on the dashboard
        <div class="Modal__actions text-center">
          <button class="btn btn-primary" onClick={onShowChanges}>
            Show Changes
          </button>
        </div>
      </div>
    </div>
  );
};
