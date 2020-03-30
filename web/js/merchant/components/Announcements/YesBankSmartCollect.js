import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcement/AnnouncementBanner';
import YesBankAnnouncement from './YesBankAnnouncement';

export default function YesBankSmartCollect({ items }) {
  if (items && Array.isArray(items) && items.length > 0) {
    return (
      <AnnouncementBanner
        class="settlement-anc"
        theme="danger"
        title="Important Announcement"
        canBeClosed={false}
      >
        <div>
          <span>
            Update on Yes bank being placed under moratorium by RBI: Incoming
            payments into your Virtual Accounts that are already created will
            not be processed at this moment. We are working on restoring this
            service as soon as possible. Please reach out to
            <a href="mailto:support.oncall@razorpay.com">
              support.oncall@razorpay.com
            </a>{' '}
            if you have any concerns.
          </span>
        </div>
      </AnnouncementBanner>
    );
  }

  return <YesBankAnnouncement />;
}
