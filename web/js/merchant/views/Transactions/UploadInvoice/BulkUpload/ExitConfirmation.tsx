import React, { useContext } from 'react';

//context
import { modalContext } from './ModalContext';
import { ModalContextType, ExitConfirmationInterface } from './types';

//components
import { ModalMask, Modal } from 'common/new-ui/Modal';

const ExitConfirmation: React.FC<ExitConfirmationInterface> = ({ closeModal }) => {
  const { setShouldShowExitPrompt } = useContext(modalContext) as ModalContextType;

  //functions
  const onContinue = () => {
    closeModal();
  };

  const onCancel = () => {
    setShouldShowExitPrompt(false);
  };

  return (
    <ModalMask>
      <Modal className="exit-confirmation-modal" showCloseBtn={false}>
        <div className="modal-header">
          <h4>Are you sure you want to exit?</h4>
        </div>
        <div className="modal-body">
          <div className="modal-description">
            <p>Invoice upload is still in progress, quitting now can result in partial upload.</p>
          </div>
          <div className="modal-buttons">
            <button className="btn btn-link" onClick={onCancel}>
              Cancel
            </button>
            <button className="btn btn-primary" onClick={onContinue}>
              Continue
            </button>
          </div>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default ExitConfirmation;
