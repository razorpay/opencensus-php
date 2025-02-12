import React from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { DocLink } from 'merchant/components/DocsLink';

export default React.memo(({ children, docURL }) => {
  return (
    <AnnouncementBanner
      title="Important Announcement!"
      theme="warning"
      card_id="card-payments-blocked-banner"
    >
      <span className="display-inline">
        {children}{' '}
        <DocLink className="btn-link" href={docURL} target="_blank">
          documentation here.
        </DocLink>
      </span>
    </AnnouncementBanner>
  );
});
