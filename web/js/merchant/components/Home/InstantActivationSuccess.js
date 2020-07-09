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
      <Modal className="instant-activations-success" onClose={onClose}>
        <modal-header>
          <h1>{title || 'Account Activated'}</h1>
          <p>{subtitle || defaultSubtitle}</p>
        </modal-header>
        <modal-body>
          {content ? (
            <p>{content}</p>
          ) : (
            <>
              <p>Congratulations, your account has been instantly activated.</p>
              <p>
                You can start using our products to accept payments right away.
                Go ahead and make your first transaction, meanwhile we will
                await your KYC details.
              </p>
            </>
          )}
          <button
            onClick={onCompleteKYC}
            className="btn btn-default ias__complete_kyc"
          >
            Complete KYC
          </button>
          <button className="btn btn-primary" onClick={onGoToDashboard}>
            Accept Payments
          </button>
        </modal-body>
      </Modal>
    </ModalMask>
  );
};
