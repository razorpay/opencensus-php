import React from 'react';
import Announcement from 'merchant/components/Announcement';

export default function YesBankAnnouncementSmartCollect() {
  const title = 'Smart Collect is up and running!';
  const description = (
    <React.Fragment>
      You can now create new Virtual Accounts via both Dashboard and APIs. Your
      integration and dashboard workflows do not require any changes. Virtual
      UPI IDs also continue to work smoothly.{' '}
      <strong>
        {' '}
        Virtual Accounts created before the Yes Bank Moratorium have been
        migrated to our new banking partner.
      </strong>{' '}
      You can learn more about how payments to these accounts can be processed{' '}
      <a
        href="https://razorpay.com/docs/smart-collect/yesbank-moratorium-migration/"
        target="_blank"
      >
        here
      </a>
      .
    </React.Fragment>
  );
  const theme = 'success';

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
