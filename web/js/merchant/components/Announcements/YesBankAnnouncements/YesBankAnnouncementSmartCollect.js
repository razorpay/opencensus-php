import React from 'react';
import Announcement from 'merchant/components/Announcement';

export default function YesBankAnnouncementSmartCollect() {
  const title = 'Razorpay Smart Collect is now up and running!';
  const description = (
    <React.Fragment>
      You can now create new Virtual Accounts via - both Dashboard and our APIs.
      Your API integration and dashboard workflows do not require any changes.
      Incoming payments into existing Virtual Accounts{' '}
      <strong>will currently not be processed.</strong> Virtual UPI-IDs continue
      to work smoothly. <span class="big-dot-separator" />
      <a
        href="https://razorpay.com/docs/smart-collect/yesbank-moratorium-migration/"
        target="_blank"
      >
        Know more
      </a>
    </React.Fragment>
  );
  const theme = 'danger';

  return (
    <Announcement
      class="settlement-anc"
      theme={theme}
      title={title}
      canBeClosed={false}
    >
      <div>{description}</div>
    </Announcement>
  );
}
