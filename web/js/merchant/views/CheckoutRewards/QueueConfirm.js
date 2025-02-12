import ModalHeader from 'common/ui/ModalHeader';
import { AsyncBtn } from 'common/new-ui/Button';

export default ({ closeModal, addToQueue }) => (
  <div className="reward-queue-confirm-modal">
    <ModalHeader title="Add to Queue" onCloseClick={closeModal} />
    <div className="modal-body">
      <p>
        Offer starting at a later date will be added to the queue and will be picked up on the
        go-live date
      </p>
      <AsyncBtn.Primary
        className="btn-block"
        disabled={false}
        onClick={() => addToQueue()}
        showLoader={false}
        pendingState="Adding..."
      >
        Add to Queue
      </AsyncBtn.Primary>
    </div>
  </div>
);
