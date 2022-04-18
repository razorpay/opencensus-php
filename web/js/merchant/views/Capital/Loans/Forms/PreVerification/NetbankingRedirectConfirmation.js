import React, { useEffect } from 'react';
import PropTypes from 'prop-types';

import lockIcon from '../../../../../../../icons/merchant/lock-filled.svg';
import PerfiosLogo from '../../../../../../../css/assets/capital/perfios-logo.svg';

import { Modal, ModalMask } from 'common/new-ui/Modal';
import { NOOP } from 'merchant/views/Capital/Loans/constants';
import { trackNetbankingModalSubmission, trackNetbankingModalLoad } from './ga';

const NetbankingRedirectConfirmation = ({ onClick, onClose }) => {
  useEffect(() => trackNetbankingModalLoad, []);

  const handleClick = () => {
    trackNetbankingModalSubmission();
    onClick();
  };

  return (
    <ModalMask>
      <Modal className="perfios__modal-wrapper" onClose={onClose} fadedCloseButton={true}>
        <div className="perfios__modal-container">
          <h1>Use Netbanking</h1>
          <h3>Securely connecting to Perfios to fetch 6 months bank statement</h3>
          <div className="flex perfios__modal-inner--wrapper">
            <div className="flex perfios__modal-inner--header">
              <div className="perfios__modal-razorpaylogo perfios__modal-logo-wrapper">
                <img
                  alt="razorpay-logo"
                  src={`${window.cdnBaseUrl}/static/assets/cash-advance/razorpayLogo.svg}`}
                />
              </div>
              <div className="perfios__modal-perfios--logo perfios__modal-logo-wrapper">
                <img src={PerfiosLogo} alt="perfios_logo" />
              </div>
            </div>
            <p className="perfios__modal-inner--text">
              You'll be re-directed to Razorpay's trusted partner <span>Perfios</span> to securely
              log in to your net banking. Please login to the bank account used for Razorpay
              settlements
            </p>
            <button className="btn btn-primary" onClick={handleClick}>
              Continue with Netbanking
            </button>
          </div>
          <div className="perfios__modal-footer">
            <div className="flex perfios__modal-footer-lockcontainer">
              <img src={lockIcon} alt="lock" />
              <p className="perfios__modal-footer--info">Security Gaurantee</p>
            </div>
            <p className="perfios__modal-footer--text">
              We use a secure, third-party service called Perfios to connect to your bank account.
              This means we can never see or store your netbanking passoword.
            </p>
          </div>
        </div>
      </Modal>
    </ModalMask>
  );
};

NetbankingRedirectConfirmation.propTypes = {
  onClick: PropTypes.func,
  onClose: PropTypes.func,
};

NetbankingRedirectConfirmation.defaultProps = {
  onClick: NOOP,
  onClose: NOOP,
};

export default NetbankingRedirectConfirmation;
