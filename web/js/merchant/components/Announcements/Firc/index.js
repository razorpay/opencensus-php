import { Link } from 'react-router-dom';
import React from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default React.memo(() => {
  return (
    <AnnouncementBanner
      title="FIRC Download"
      card_id="view-firc-banner"
      canBeClosed={true}
      theme="warning"
    >
      <span className="display-inline">
        Wait no more to get your FIRC. You can now download your monthly transaction certificate
        directly from dashboard.
      </span>
      <Link
        to="/profile/view_firc"
        className="Button--secondary Button scheduled-btn-act btn-border"
      >
        View / Download
      </Link>
    </AnnouncementBanner>
  );
});
