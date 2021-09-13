import React, { useEffect } from 'react';
import { Link } from 'react-router-dom';
import Button from 'common/new-ui/Button';
import { ModalMask, Modal } from 'common/new-ui/Modal';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const SuccessTnCGeneratedModal = ({ onCloseModal }) => {
  useEffect(() => {
    analyticsTrack({
      objectName: 'successfully MTU offer',
      actionName: 'applied',
      screen: 'home page',
      properties: {
        location: 'top header',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }, []);

  return (
    <ModalMask>
      <Modal className="pan-status-modal" onClose={onCloseModal}>
        <div className="modal-header completed">
          <h1>Congrats! You have received free credits in your account</h1>
        </div>
        <div className="modal-body">
          <div className="modal-description success-redeem">
            <p className="coupon-content">
              You will not be charged any fees until 2 Lakhs worth of payments. You can check your
              free credits in the{' '}
              <Link to="/credits" className="link" onClick={onCloseModal}>
                credits section
              </Link>{' '}
              in my account
            </p>
            <Button.Primary type="button" onClick={onCloseModal}>
              Okay, Got it
            </Button.Primary>
          </div>
        </div>
      </Modal>
    </ModalMask>
  );
};

export default SuccessTnCGeneratedModal;
