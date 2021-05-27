import React from 'react';
import RTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { CreateTicketEmitter } from '../../views/TicketSupport/utils';

const SupportButton = ({ type, buttonLabel, category, openSection, tracking }) => {
  const handleClick = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('act.contact_support.click', {
        clicked_on: 'Contact Support Button',
        status: 'Merchant_support',
      }),
    );
    analyticsTrack({
      objectName: 'contact support',
      actionName: 'clicked',
      screen: 'On contact support button click ',
      properties: {
        location: 'Contact Support Button',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      CreateTicketEmitter.emit(
        'create-ticket',
        'ticket',
        () => {
          rzpTicketSystem.setPrefill('#request', [category, openSection]);
        },
        () => {
          setTimeout(() => {
            rzpTicketSystem.modal.nextButton.click();
          }, 0);
        },
      );
    }
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
