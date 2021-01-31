import React from 'react';
import RTracking from 'react-tracking';

const SupportButton = ({ type, buttonLabel, category, openSection, tracking }) => {
  const handleClick = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('act.contact_support.click', {
        clicked_on: 'Contact Support Button',
        status: 'Merchant_support',
      }),
    );
    rzpTicketSystem.setPrefill('#request', [category, openSection]);
    rzpTicketSystem.openModal('#ticket');
    setTimeout(() => {
      rzpTicketSystem.modal.nextButton.click();
    }, 0);
  };

  if (type === 'button') {
    return (
      <button className="btn btn-primary" onClick={() => handleClick()}>
        {buttonLabel}
      </button>
    );
  } else {
    return <a onClick={() => handleClick()}>{buttonLabel}</a>;
  }
};

export default RTracking({ page: 'SupportButton' })(SupportButton);
