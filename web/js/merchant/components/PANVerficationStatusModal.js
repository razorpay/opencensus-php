import React from 'react';
import { ModalMask, Modal } from 'component/Modal';

const PAN = 'PAN';
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
      <>
        <p>
          You can start accepting payment now by integrating with a website/app
          or using other products.
        </p>
        <p>You can accept upto ₹10,000 per transaction. know more</p>
      </>
    ),
    background: 'success',
  },
};

const PANVerficationStatusModal = ({ user, onClose, onGoToDashboard }) => {
  console.log(user);
  console.log(user.poi_verification_status);
  const content =
    MODAL_CONTENT[VERIFICATION_STATUS[user.poi_verification_status]];
  console.log('content', content);
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

export default PANVerficationStatusModal;
