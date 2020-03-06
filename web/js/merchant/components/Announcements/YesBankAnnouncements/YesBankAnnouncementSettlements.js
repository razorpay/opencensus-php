import React from 'react';
import Announcement from 'merchant/components/Announcement';

export default function YesBankAnnouncementSettlements() {
  const title = 'Settlements on hold';
  const description = (
    <React.Fragment>
      Settlements are on hold as you have a Yes bank settlement. If you have an
      alternate bank account where you can receive settlements, you can update
      it with us or contact support for help in opening a new account on
      priority with our banking partners.
      <span class="big-dot-separator" />
      <a
        href="https://razorpay.com/docs/payment-gateway/dashboard-guide/my-account/?utm_campaign=Yes%20Bank%20Moratarium&utm_source=hs_email&utm_medium=email&_hsenc=p2ANqtz-9wvLkNiY0CP_1F-2nnr94JTdXuL9v8NGHG0PWC-LcjrD7dPwyRZ8cC0r8Wf7WstxswPR0l#change-bank-account-details"
        target="_blank"
      >
        Update account
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
