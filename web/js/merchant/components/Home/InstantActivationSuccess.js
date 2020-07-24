import React from 'react';
import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

export default ({
  onClose,
  onGoToDashboard,
  onCompleteKYC,
  title,
  subtitle,
  content,
  user,
}) => {
  let defaultSubtitle = 'Ready to accept domestic payments';

  if (user.international) {
    defaultSubtitle = 'Ready to accept domestic & international payments';
  }

  return (
    <ModalMask>
      <Modal className="pan-status-modal" onClose={onClose}>
        <div className="modal-header success">
          <h1>{title || 'Payments Enabled'}</h1>
          <p>{subtitle || defaultSubtitle}</p>
        </div>
        <div className="modal-body">
          <div className="modal-description">
            {content ? (
              <p>{content}</p>
            ) : (
              <>
                <p>
                  Congratulations! You can start accepting payments from your
                  customers now.
                </p>
                <p>
                  However, you must complete KYC for the payments to be settled
                  to your account.
                </p>
              </>
            )}
          </div>
          <button
            onClick={onCompleteKYC}
            className="btn btn-default KYC__more_details"
          >
            Complete KYC
          </button>
          <button className="btn btn-primary" onClick={onGoToDashboard}>
            Accept Payments
          </button>
        </div>
      </Modal>
    </ModalMask>
  );
};
