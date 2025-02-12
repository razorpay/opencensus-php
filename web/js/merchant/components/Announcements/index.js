import React from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

export default React.memo(({ title, canBeClosed, theme, key, id, message }) => {
  return (
    <AnnouncementBanner
      title={title}
      canBeClosed={canBeClosed}
      theme={theme}
      bannerKey={key}
      card_id={id}
    >
      <span className="display-inline">{message}</span>
    </AnnouncementBanner>
  );
});
