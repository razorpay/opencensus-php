import React from 'react';
import Button from 'common/new-ui/Button';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import GenerateTnCPage from './GenerateTnCPage';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const TnCModal = ({ onClose, closeModal, openModal, tracking }) => {
  const generatePage = () => {
    onClose();
    openModal({
      size: 'small',
      component: <GenerateTnCPage onCloseModal={closeModal} openModal={openModal} />,
    });
    tracking.trackEvent(
      window.rzpQ.onbr().initiated('act.generate_page_now', {
        clickSource: 'KYC submitted, TnC Popup',
      }),
    );
    analyticsTrack({
      objectName: 'Act Generate Page Now',
      actionName: 'initiated',
      screen: 'home page',
      properties: {
        clickSource: 'post activation tnc popup',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  return (
    <ModalMask>
      <Modal className="pan-status-modal" onClose={onClose}>
        <div className="modal-header pending">
          <h1>KYC has been submitted, Generate your TnC Page Now</h1>
        </div>
        <div className="modal-body">
          <div className="modal-description tnc-desc">
            <div>
              <p>
                You have submitted all the KYC details, Now you just have to generate the Terms and
                conditions page.
              </p>
              <p>
                We will start reviewing your KYC once the page has been generated. Review usually
                takes 3-4 days. Account activation is subject to terms and conditions generation and
                this is a mandatory step.
              </p>
            </div>
            <Button.Primary onClick={generatePage}>Generate TnC Page</Button.Primary>
          </div>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default TnCModal;
