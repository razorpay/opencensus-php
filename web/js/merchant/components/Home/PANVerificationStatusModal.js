import React from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';

const PAN_FAILURE = 'PAN_FAILURE'; // timeout in PAN verification
const PAN_SUCCESS = 'PAN_SUCCESS'; // timeout in PAN verification

const VERIFICATION_STATUS = {
  failed: PAN_FAILURE,
  verified: PAN_SUCCESS,
};

const MODAL_CONTENT = {
  PAN_FAILURE: {
    title: 'PAN Under Review',
    subtitle: 'We are trying to review your PAN',
    body: () => (
      <p>
        This seems to be taking longer than usual. You can explore the dashboard
        while we review your PAN Details.
      </p>
    ),
    background: 'pending',
  },
  PAN_SUCCESS: {
    title: 'Account Activated',
    subtitle: 'PAN successfully verified',
    body: () => (
      <p>
        Congratulations! You are ready to accept payments. You can integrate
        with a website/app or use one of our products.
      </p>
    ),
    background: 'success',
  },
};

const PANVerificationStatusModal = ({ user, onClose, onGoToDashboard }) => {
  const content =
    MODAL_CONTENT[VERIFICATION_STATUS[user.poi_verification_status]];
  return (
    <ModalMask>
      <Modal className="pan-status-modal" onClose={onClose}>
        <div className={`modal-header ${content.background}`}>
          <h1>{content.title}</h1>
          <p>{content.subtitle}</p>
        </div>
        <div className="modal-body">
          <div className="modal-description">{content.body()}</div>
          <button className="btn btn-primary" onClick={onGoToDashboard}>
            Go to Dashboard
          </button>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default PANVerificationStatusModal;
