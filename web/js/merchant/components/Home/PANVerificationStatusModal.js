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
    title: 'Payments Enabled',
    subtitle: 'Ready to accept domestic payments',
    body: () => (
      <>
        <p>
          Congratulations! You can start accepting payments from your customers
          now.
        </p>
        <p>
          However, you must complete KYC for the payments to be settled to your
          account.
        </p>
      </>
    ),
    background: 'success',
  },
};

const ModalButtons = ({ args }) => {
  if (VERIFICATION_STATUS[args.poiVerificationStatus] === PAN_SUCCESS) {
    return (
      <>
        <button
          className="btn btn-default KYC__more_details"
          onClick={args.onCompleteKYC}
        >
          Complete KYC
        </button>
        <button className="btn btn-primary" onClick={args.onGoToDashboard}>
          Accept Payments
        </button>
      </>
    );
  }

  return (
    <button className="btn btn-primary" onClick={args.onGoToDashboard}>
      Go to Dashboard
    </button>
  );
};

const PANVerificationStatusModal = ({
  user,
  onClose,
  onGoToDashboard,
  onCompleteKYC,
}) => {
  const content =
    MODAL_CONTENT[VERIFICATION_STATUS[user.poi_verification_status]];

  const args = {
    onGoToDashboard: onGoToDashboard,
    onCompleteKYC: onCompleteKYC,
    poiVerificationStatus: user.poi_verification_status,
  };

  return (
    <ModalMask>
      <Modal className="pan-status-modal" onClose={onClose}>
        <div className={`modal-header ${content.background}`}>
          <h1>{content.title}</h1>
          <p>{content.subtitle}</p>
        </div>
        <div className="modal-body">
          <div className="modal-description">{content.body()}</div>
          <ModalButtons args={args} />
        </div>
      </Modal>
    </ModalMask>
  );
};

export default PANVerificationStatusModal;
