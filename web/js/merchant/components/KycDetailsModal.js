import React, { Component } from 'react';
import { Link } from 'react-router-dom';

import { ModalMask, Modal, ModalContent } from 'component/Modal';

export default ({ onClose, onGiveDetails }) => {
  return (
    <ModalMask>
      <Modal
        className="kyc-details-modal"
        onClose={onClose}
        style={{ textAlign: 'center' }}
      >
        <p>
          <b>Few more details required</b>
        </p>
        <p>
          For your business model, you need to give a few more details to
          activate your account
        </p>
        <Link
          to="/activation"
          className="btn btn-primary"
          onClick={onGiveDetails}
        >
          Give Details
        </Link>
      </Modal>
    </ModalMask>
  );
};
