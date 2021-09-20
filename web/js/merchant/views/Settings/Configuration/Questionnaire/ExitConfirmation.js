import React from 'react';
import Questionnaire from './index';
import { connect } from 'react-redux';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import {
  openModal as openModalFn,
  closeModal as closeModalFn,
} from 'merchant_common/reducers/modals';

const SCREEN = window.location.pathname.includes('payment-methods') ? 'payment methods' : 'config';

const ExitConfirmation = ({ closeModal, openModal, saveFormData, triggerSource }) => {
  const saveDraft = () => {
    saveFormData();
    closeModal();
    analyticsTrack({
      objectName: 'intl enablement form',
      actionName: `click close Save as draft`,
      screen: SCREEN,
      properties: {
        timestamp: Date.now(),
        ...getCommonAnalyticsProperties(window.rzp_user),
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
              saveFormData().then(() => {
                openModal({
                  component: <Questionnaire triggerSource={triggerSource} />,
                  overlayStyles: {
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                  },
                });
              });
            }}
            class="btn btn-link"
          >
            Cancel
          </button>
          <button onClick={saveDraft} class="btn btn-primary">
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
