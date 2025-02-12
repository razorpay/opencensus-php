import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { DocLink } from 'merchant/components/DocsLink';

function UpdatePaymentMethodBanner({ url }) {
  return (
    <AnnouncementBanner title="IMPORTANT UPDATE" canBeClosed={true} theme="warning">
      <span className="display-inline">
        Activate your &#39;pending&#39; and &#39;halted&#39; subscriptions by updating payment
        methods!
      </span>
      <DocLink className="btn btn-link" href={url} target="_blank">
        Click here to know more
      </DocLink>
    </AnnouncementBanner>
  );
}

export default React.memo(UpdatePaymentMethodBanner);
