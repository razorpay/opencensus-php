import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { DocLink } from 'merchant/components/DocsLink';

export default React.memo(({ url }) => {
  return (
    <AnnouncementBanner title="IMPORTANT UPDATE" canBeClosed={true} theme="warning">
      <span class="display-inline">
        Activate your &#39;pending&#39; and &#39;halted&#39; subscriptions by updating payment
        methods!
      </span>
      <DocLink class="btn btn-link" href={url} target="_blank">
        Click here to know more
      </DocLink>
    </AnnouncementBanner>
  );
});
