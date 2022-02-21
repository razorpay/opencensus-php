import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { trackMarketingExperimentBanner } from '../ga';
import { CreateTicketEmitter } from '../../../views/TicketSupport/utils';

export default ({ userId }) => {
  trackMarketingExperimentBanner('CovidCampaign', 'Appear');

  const raiseTicket = () => {
    if (window.rzpTicketSystem) {
      CreateTicketEmitter.emit('create-ticket', 'tickets');

      setTimeout(() => {
        document.getElementsByName('request-description')[0].value =
          'Hello Team,\n' +
          'I’d like to know if I can avail the free credits and same day settlement for my account on Razorpay';
      }, 1000);
    }
  };

  return (
    <AnnouncementBanner
      theme="success"
      title="COVID 19 Relief Update"
      canBeClosed={true}
      bannerKey={`covid-campaign-banner-${userId}`}
      card_id="covid-campaign-banner"
    >
      <div style={{ display: 'flex', alignItems: 'center' }}>
        <div>
          We’re here to help you ensure business continuity in wake of COVID-19.&nbsp; Reach out to
          us to check the eligibility for free credits or same day settlements.&nbsp; Drop us a note
          at&nbsp;
          <a
            href="mailto:covid-19relief@razorpay.com"
            target="_blank"
            rel="noreferrer noopener"
            onClick={() => trackMarketingExperimentBanner('CovidCampaign', 'Click Link')}
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
    </AnnouncementBanner>
  );
};
