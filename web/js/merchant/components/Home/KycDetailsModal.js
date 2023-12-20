import React from 'react';
import { Link } from 'react-router-dom';
import Button from 'common/new-ui/Button';

import { ModalMask, Modal } from 'common/new-ui/Modal';

export default ({ onClose, onGiveDetails }) => {
  return (
    <ModalMask>
      <Modal className="kyc-details-modal" onClose={onClose} style={{ textAlign: 'left' }}>
        <div className="modal-header">
          <b>Few more details required</b>
        </div>
        <p className="kyc-details-modal__desc">
          For your business model, you need to give a few more details to activate your account
        </p>
        <div className="kyc-details-modal__btn">
          <Button.Secondary onClick={onClose}>Do it later</Button.Secondary>
          <Link to="/activation" className="Button Button--primary" onClick={onGiveDetails}>
            Give Details
          </Link>
        </div>
      </Modal>
    </ModalMask>
  );
};
