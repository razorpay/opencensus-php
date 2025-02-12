import React from 'react';
import { CreateTicketEmitter } from '../../TicketSupport/utils';
import trackAutomatedCA from './ga/automated';

const SuccesfullyEnabledModal = ({ onClose }) => {
  const handleDoneClick = () => {
    trackAutomatedCA.clickDoneInSuccessfullyEnabled({});
    onClose();
  };
  const handleContactSupportClick = () => {
    trackAutomatedCA.clickContactSupportInSuccessfullyEnabled({});
    if (window.rzpTicketSystem) {
      CreateTicketEmitter.emit(
        'create-ticket',
        'tickets',
      );
    }
  };
  const handleModalCloseClick = () => {
    trackAutomatedCA.clickCloseModalInSuccessfullyEnabled({});
    onClose();
  };
  return (
    <div className="successfully-enabled-modal">
      <div className="cross-btn" onClick={handleModalCloseClick}>
        <i className="i i-close" />
      </div>
      <div className="successfully-enabled-modal--heading">
        <img src={require("assets/capital/hurray_tick.svg")} />
        <div className="successfully-enabled-modal--title">Successfully Enabled!</div>
      </div>
      <div className="successfully-enabled-modal--description">
        Congratulations! We’ve successfully enabled the automated cash withdrawal feature.
      </div>
      <div className="successfully-enabled-subModal">
        <div className="successfully-enabled-subModal-description">
          Your next withdrawal will be automatically credited to your account as soon as you repay
          your current outstanding due amount.
        </div>
        <button className="btn btn-primary done-btn" onClick={handleDoneClick}>
          Done
        </button>
      </div>
      <div
        onClick={handleContactSupportClick}
        className="successfully-enabled-modal-contact-support"
      >
        Contact Support
      </div>
    </div>
  );
};

export default SuccesfullyEnabledModal;
