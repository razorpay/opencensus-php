import React from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import {
  checkEligibilityForFeeBasedGating,
  handleFeeBasedGatingNavigation,
} from 'merchant/utils/feeBasedGatingUtils';

export const FtuxModal = ({ closeModal, handleModalVisibilty, user }) => {
  const isEligibleForFeeBasedGating = checkEligibilityForFeeBasedGating(user);
  const handleAccountSetup = () => {
    handleModalVisibilty();
    if (isEligibleForFeeBasedGating) {
      handleFeeBasedGatingNavigation({ ctaLocation: 'FtuxModal' });
    } else {
      window.open(`${window.EASY_ONBOARDING_URL}/overview`, '_self', 'noopener');
    }
  };
  const handleCloseModal = () => {
    handleModalVisibilty();
    closeModal();
  };
  return (
    <ModalMask>
      <Modal className="pan-status-modal nc-modal" showCloseBtn={false}>
        <div className="modal-body">
          <h1>Collect a payment to complete your account setup</h1>
          <div className="modal-description">
            Go back to the account setup page or choose a payment product from the main menu to to
            collect a payment
          </div>
          <button className="btn btn-primary fullWidth" onClick={handleAccountSetup}>
            Back to account setup
          </button>
          <button className="btn btn-link link-button" onClick={handleCloseModal}>
            Stay on dashboard
          </button>
        </div>
      </Modal>
    </ModalMask>
  );
};
