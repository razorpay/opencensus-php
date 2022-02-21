import React from 'react';

import { ModalMask, Modal } from 'common/new-ui/Modal';

const FraudDetectionModal = ({ onClose }) => {
  return (
    <ModalMask>
      <Modal className="pan-status-modal" onClose={onClose}>
        <div className="modal-header pending">
          <h1>Reach out to us</h1>
          <p>Contact our support to start transacting</p>
        </div>
        <div className="modal-body">
          <div className="modal-description fraud-detection">
            <div>
              <p>We need some more information regarding your submitted details.</p>
              <p>
                Please{' '}
                <a
                  href="https://razorpay.com/support/#request"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  contact our support
                </a>{' '}
                and they will assist you further
              </p>
            </div>
            <button className="btn btn-primary" onClick={onClose}>
              Okay got it
            </button>
          </div>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default FraudDetectionModal;
