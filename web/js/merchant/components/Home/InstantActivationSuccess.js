import React from 'react';
import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';

export default ({
  onClose,
  onGoToDashboard,
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
              <p>
                You can now start accepting payment by integrating with a
                website/app or using other products.
              </p>
              <p>
                However, payments will be settled to your account only after KYC
                verification.
              </p>
            </>
          )}
          <button className="btn btn-primary" onClick={onGoToDashboard}>
            Go to Dashboard
          </button>
        </modal-body>
      </Modal>
    </ModalMask>
  );
};
