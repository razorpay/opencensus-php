import React from 'react';
import Announcement from 'merchant/components/Announcement';
import { trackMarketingExperimentBanner } from '../ga';

export default ({ userId }) => {
  trackMarketingExperimentBanner('CovidCampaign', 'Appear');

  const raiseTicket = () => {
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      rzpTicketSystem.setPrefill('#request', [
        'merchant',
        'covid-19-relief-query',
      ]);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
      setTimeout(() => {
        document.getElementsByName('request-description')[0].value =
          'Hello Team,\n' +
          'I’d like to know if I can avail the free credits and same day settlement for my account on Razorpay';
      }, 1000);
    }
  };

  return (
    <Announcement
      theme="success"
      title="COVID 19 Relief Update"
      canBeClosed={true}
      bannerKey={`covid-campaign-banner-${userId}`}
    >
      <div style={{ display: 'flex', alignItems: 'center' }}>
        <div>
          We’re here to help you ensure business continuity in wake of
          COVID-19.&nbsp; Reach out to us to check the eligibility for free
          credits or same day settlements.&nbsp; Drop us a note at&nbsp;
          <a
            href="mailto:covid-19relief@razorpay.com"
            target="_blank"
            onClick={() =>
              trackMarketingExperimentBanner('CovidCampaign', 'Click Link')
            }
          >
            covid-19relief@razorpay.com
          </a>
          &nbsp;or raise a support ticket.
        </div>
        <button class="btn btn-outline" onClick={raiseTicket}>
          Raise a ticket
          <i className="i i-chevron-right" />
        </button>
      </div>
    </Announcement>
  );
};
