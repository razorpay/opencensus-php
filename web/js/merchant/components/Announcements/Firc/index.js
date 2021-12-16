import { Link } from 'react-router-dom';
import React, { useCallback } from 'react';

import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

export default React.memo((props) => {
  const { screen } = props;

  const clickHandler = useCallback(() => {
    analyticsTrack({
      objectName: 'FIRC Banner',
      actionName: 'clicked',
      screen,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  }, [screen]);

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
        className="Button--secondary firc-banner-cta Button scheduled-btn-act btn-border"
        onClick={clickHandler}
      >
        View / Download
      </Link>
    </AnnouncementBanner>
  );
});
