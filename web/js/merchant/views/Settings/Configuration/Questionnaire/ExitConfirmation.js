import React from 'react';
import { connect } from 'react-redux';
import { merchantFetch } from 'merchant/utils/ajax';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import { showNotification } from 'merchant_common/reducers/notifications';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const SCREEN = window.location.pathname.includes('payment-methods') ? 'payment methods' : 'config';

// eslint-disable-next-line no-shadow
const ExitConfirmation = ({ closeModal, saveFormData, showNotification }) => {
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

  const discardData = () => {
    analyticsTrack({
      objectName: 'intl enablement form',
      actionName: `click close Discard form data`,
      screen: SCREEN,
      properties: {
        timestamp: Date.now(),
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    merchantFetch({
      url: 'international_enablement',
      method: 'delete',
    })
      .then(() => {
        closeModal();
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err.errors,
        });
        closeModal();
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
          <button onClick={discardData} class="btn btn-link">
            Discard
          </button>
          <button onClick={saveDraft} class="btn btn-primary">
            Save As Draft
          </button>
        </footer>
      </ModalContent>
    </Modal>
  );
};

export default connect(null, { showNotification })(ExitConfirmation);
