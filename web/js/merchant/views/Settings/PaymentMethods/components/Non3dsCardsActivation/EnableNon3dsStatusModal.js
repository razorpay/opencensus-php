import React, { useState } from 'react';

// components
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';

/**
 *
 * @param {{ open: Function, onClose: Function }} param0 Props to control the modal
 * @returns {React.ReactNode} Return EnableNon3ds Modal
 */
const EnableNon3dsStatusModal = ({ open, isLoading, onClose, onEnable }) => {
  const [chargeBackConsentChecked, setChargeBackConsentChecked] = useState(false);

  const handleChargeBackConsentChange = (e) => {
    setChargeBackConsentChecked(e.target.checked);
  };

  const handleEnable = () => {
    onEnable(chargeBackConsentChecked);
  };

  if (!open) {
    return null;
  }

  return (
    <ModalMask>
      <Modal onClose={onClose} className="non3dsCardsModal">
        <ModalContent>
          <div className="non-3ds-modal-header">
            <h3>Enable non 3D Secure Cards</h3>
          </div>
          <div className="non-3ds-modal-content">
            <div>
              You are requesting to enable Non 3D Secure card support. Enabling non 3D Secure card
              support might have fraud risks.
            </div>
            <div>
              <Input.Check
                value={chargeBackConsentChecked}
                fieldLabel="I understand that Razorpay will have no charge-back liabilities in case of fraud transactions."
                onChange={handleChargeBackConsentChange}
              />
            </div>
            <div className="enable-actions">
              <Button onClick={onClose}>Cancel</Button>
              <Button.Primary
                onClick={handleEnable}
                disabled={isLoading || !chargeBackConsentChecked}
              >
                Request
              </Button.Primary>
            </div>
          </div>
        </ModalContent>
      </Modal>
    </ModalMask>
  );
};

export default EnableNon3dsStatusModal;
