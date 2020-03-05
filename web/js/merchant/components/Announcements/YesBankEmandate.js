import React from 'react';
import Announcement from 'merchant/components/Announcement';
import YesBankAnnouncement from './YesBankAnnouncement';

export default function YesBankEmandate({ user, payments }) {
  if (
    user.isChargeAtWillEnabled &&
    payments &&
    Array.isArray(payments.items) &&
    payments.items.length > 0
  ) {
    return (
      <Announcement
        class="settlement-anc"
        theme="danger"
        title="Important Announcement"
        canBeClosed={false}
      >
        <div>
          <span>
            Update on Yes bank being placed under moratorium by RBI: E-mandate
            registrations and debit presentations via NPCI are facing some
            issues. Our team is working to ensure there is no disruption in
            services. Please reach out to{' '}
            <a href="mailto:support.oncall@razorpay.com">
              support.oncall@razorpay.com
            </a>{' '}
            if you have any concerns.
          </span>
        </div>
      </Announcement>
    );
  }

  return <YesBankAnnouncement />;
}
