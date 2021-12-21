import React from 'react';
import { Link } from 'react-router-dom';
import { ModalMask, Modal } from 'common/new-ui/Modal';

const NCModal = ({ isActivationFormFullView, onClose }) => {
  const activationUrl = isActivationFormFullView ? '/kyc' : '/activation';
  return (
    <ModalMask>
      <Modal className="nc-status-modal" onClose={onClose}>
        <div className="modal-header warning">
          <h1>KYC Clarification</h1>
        </div>
        <div className="modal-body">
          <div className="modal-description">
            <div>
              <p>
                We have a few questions about your KYC submission, please provide required
                clarifications at the earliest for quick account activation. Please note,
                settlements will only be enabled to your bank account, after your revised KYC is
                reviewed and approved.
              </p>
            </div>
          </div>
          <Link to={activationUrl}>
            <button className="btn btn-primary" onClick={onClose}>
              Add Clarifications
            </button>
          </Link>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default NCModal;
