import React, { Component } from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default class YesBankAnnouncement extends Component {
  render() {
    return (
      <AnnouncementBanner
        class="settlement-anc"
        theme="danger"
        title="Important Announcement"
        canBeClosed={false}
      >
        <div>
          <span>
            Update on Yes bank being placed under moratorium by RBI: Our payment
            gateway services are completely unaffected. Some other services may
            get affected. Our team is working to ensure there is no disruption
            in services. Please reach out to{' '}
            <a href="mailto:support.oncall@razorpay.com">
              support.oncall@razorpay.com
            </a>{' '}
            if you have any concerns.
          </span>
        </div>
      </AnnouncementBanner>
    );
  }
}
