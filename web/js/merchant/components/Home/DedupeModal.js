import React, { useState } from 'react';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import SupportButton from './SupportButton';

const DedupeModal = () => {
  const [showDedupeModal, setShowDedupeModal] = useState(true);

  const onDedupeModalClose = () => {
    setShowDedupeModal(false);
  };

  return (
    showDedupeModal && (
      <ModalMask>
        <Modal className="nc-status-modal" onClose={() => onDedupeModalClose()}>
          <div className="modal-header suspect-warning">
            <h1>KYC Clarification</h1>
          </div>
          <div className="modal-body">
            <div className="modal-description">
              <div>
                <p>
                  We need some more information regarding your submitted details. Please contact us
                  to activate your account.
                </p>
              </div>
            </div>
            <SupportButton
              type="button"
              buttonLabel="Contact Support"
              category="merchant"
              openSection="account-activation"
            />
          </div>
        </Modal>
      </ModalMask>
    )
  );
};

export default DedupeModal;
