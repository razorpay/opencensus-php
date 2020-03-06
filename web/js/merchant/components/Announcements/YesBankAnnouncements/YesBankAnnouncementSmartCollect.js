import React from 'react';
import Announcement from 'merchant/components/Announcement';

export default function YesBankAnnouncementSmartCollect() {
  const title = 'Bank transfer on Hold for Virtual Accounts';
  const description = (
    <React.Fragment>
      All bank transfers (NEFT, RTGS,IMPS) to existing & new virtual accounts
      will be on hold. However, virtual UPI IDs (i.e. VPAs) are working as
      usual. We are working tirelessly to bring full functionality back to
      virtual accounts & will update you soon.
      <span class="big-dot-separator" />
      <a
        href="https://lp.razorpay.com/unregistered-businesses-faqs-0"
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
