import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { DocLink } from 'merchant/components/DocsLink';

export default React.memo(({ url }) => {
  return (
    <AnnouncementBanner title="IMPORTANT UPDATE" canBeClosed={true} theme="warning">
      <span class="display-inline">
        On-board customers using Cash Credit, NRE and NRO accounts with Razorpay Paper NACH.
      </span>
      <DocLink class="btn btn-link" href={url} target="_blank">
        Know more
      </DocLink>
    </AnnouncementBanner>
  );
});
