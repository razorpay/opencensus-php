import React from 'react';
import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'component/Modal';

export default ({ onClose, title, subtitle, content }) => {
  return (
    <ModalMask>
      <Modal className="instant-activations-success" onClose={onClose}>
        <modal-header>
          <h1>{title || 'Account Activated'}</h1>
          <p>{subtitle || 'Ready to accept domestic payments'}</p>
        </modal-header>
        <modal-body>
          {content ? (
            <p>{content}</p>
          ) : (
            <p>
              Now you can start accepting domestic payments from your customers.
              However, your payments will be settled only after completing the
              KYC.
            </p>
          )}
          <button className="btn btn-primary" onClick={onClose}>
            Go to Dashboard
          </button>
        </modal-body>
      </Modal>
    </ModalMask>
  );
};
