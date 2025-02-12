import React from 'react';
import Questionnaire from './index';
import { connect } from 'react-redux';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import {
  openModal as openModalFn,
  closeModal as closeModalFn,
} from 'merchant_common/reducers/modals';
import { trackFormButtonClicked, trackModalClosed } from './analytics';
import { tabsData } from './utils';

const ExitConfirmation = ({
  closeModal,
  openModal,
  saveFormData,
  triggerSource,
  activeTab,
  isRevampFlow,
}) => {
  const saveDraft = () => {
    saveFormData();
    closeModal();
    trackFormButtonClicked(tabsData?.[activeTab]?.name, 'Save and Exit');
    trackModalClosed();
  };

  const openQuestionnaireModal = () => {
    openModal({
      component: <Questionnaire triggerSource={triggerSource} isRevampFlow={isRevampFlow} />,
      overlayStyles: {
        display: 'flex',
        justifyContent: 'center',
        alignItems: 'center',
      },
    });
  };

  return (
    <Modal className="save-draft" showCloseBtn={false}>
      <ModalContent>
        <header>
          <h4>
            <strong>Are you sure you want to exit</strong>
          </h4>
        </header>
        <br />
        <main>
          <p>You have unsaved changes on the form to enable international payments</p>
        </main>
        <br />
        <footer>
          <button
            onClick={() => {
              if (isRevampFlow) {
                openQuestionnaireModal();
              } else {
                saveFormData().then(() => {
                  openQuestionnaireModal();
                });
              }
            }}
            className="btn btn-link"
          >
            Cancel
          </button>
          <button onClick={saveDraft} className="btn btn-primary">
            Save and Exit
          </button>
        </footer>
      </ModalContent>
    </Modal>
  );
};

export default connect(null, {
  openModal: openModalFn,
  closeModal: closeModalFn,
})(ExitConfirmation);
