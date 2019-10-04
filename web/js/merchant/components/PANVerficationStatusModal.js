import React from 'react';
import { ModalMask, Modal } from 'component/Modal';

const PANVerficationStatusModal = ({ user }) => {
  return (
    <ModalMask>
      <Modal className="pan-status-modal">
        <div className="modal-header">
          <h1>PAN Under Review</h1>
          <p>PAN successfully verified</p>
        </div>
        <div className="modal-body">
          <div className="modal-description">
            <p>
              You can start accepting payment now by integrating with a
              website/app or using other products.
            </p>
            <p>You can accept upto ₹10,000 per transaction. know more</p>
          </div>
          <button className="btn btn-primary">Go to Dashboard</button>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default PANVerficationStatusModal;
