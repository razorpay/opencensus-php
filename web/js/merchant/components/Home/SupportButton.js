import React from 'react';
import RTracking from 'react-tracking';
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const SupportButton = ({ type, buttonLabel, category, openSection, tracking }) => {
  const handleClick = () => {
    tracking.trackEvent(
      window.rzpQ.merchantActions().initiated('act.contact_support.click', {
        clicked_on: 'Contact Support Button',
        status: 'Merchant_support',
      }),
    );
    analyticsService.track({
      objectName: 'act.contact_support',
      actionName: 'click',
      screen: 'On contact support button click ',
      properties: {
        location: 'Contact Support Button',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
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
