import React from 'react';
import { NOOP } from 'merchant/views/Capital/Loans/constants';
import PropTypes from 'prop-types';
import lockIcon from '../../../../../../icons/merchant/lock-filled.svg';
import LeegalityIcon from '../../../../../../css/assets/capital/leegality.png';
import Button from 'common/new-ui/Button';
import { setItem } from 'common/utils/localStorage';

const GromorRedirectConfirmation = ({ onClose, eSignUrl, email_id, name }) => {
  const handleClick = () => {
    setItem('loc_esign_clicked', true);
  };
  return (
    <div>
      <div className="leegality-redirect-container">
        <h1>Re-directing to Leegality</h1>
        <h3>Securely sign your new lender agreementt</h3>
        <button className="close" onClick={onClose}>
          <i className="i i-close" />
        </button>
        <div className="flex leegality-redirect-inner-wrapper">
          <div className="flex leegality-redirect-inner-header">
            <div className="leegality-redirect-razorpaylogo leegality-redirect-logo-wrapper">
              <img
                alt="razorpay-logo"
                src={`${window.cdnBaseUrl}/static/assets/cash-advance/razorpayLogo.svg}`}
              />
            </div>
            <div className="leegality-redirect-leegality-logo leegality-redirect-logo-wrapper">
              <img src={LeegalityIcon} alt="leegality_logo" />
            </div>
          </div>
          <p className="leegality-redirect-inner-text">
            {name} will receive an OTP on the <br /> Email ID: <span> {email_id} </span> registered
            with Razorpay. This is required to complete the e-sign.
          </p>
          <a href={eSignUrl} onClick={handleClick} rel="noopener noreferrer">
            <Button.Primary className="cta text">Continue with Leegality</Button.Primary>
          </a>
        </div>
        <div className="leegality-redirect-footer">
          <div className="flex leegality-redirect-footer-lockcontainer">
            <img src={lockIcon} alt="lock" />
            <p className="leegality-redirect-footer-info">Security Gaurantee</p>
          </div>
          <p className="leegality-redirect-footer-text">
            We use a secure, third-party service called leegality to securly sign the new lender
            agreement.
          </p>
        </div>
      </div>
    </div>
  );
};

GromorRedirectConfirmation.propTypes = {
  onClose: PropTypes.func,
  eSignUrl: PropTypes.string,
  email_id: PropTypes.string,
  name: PropTypes.string,
};

GromorRedirectConfirmation.defaultProps = {
  onClose: NOOP,
  eSignUrl: '',
  email_id: '',
  name: 'You',
};

export default GromorRedirectConfirmation;
