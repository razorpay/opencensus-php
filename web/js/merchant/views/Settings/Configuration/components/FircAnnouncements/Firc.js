import React from 'react';
import { Link } from 'react-router-dom';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

const clickHandler = () => {
  analyticsTrack({
    objectName: 'FIRC Banner',
    actionName: 'clicked',
    screen: 'Settings',
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
    },
    toLumberjack: true,
  });
};

const Firc = () => {
  return (
    <div className="firc-settings-banner">
      <img
        src={`${window.cdnBaseUrl}/static/assets/firc/banner-border.png`}
        alt="Banner border"
        width="35"
        height="44"
      />
      <img
        src={`${window.cdnBaseUrl}/static/assets/firc/blue_vector.svg`}
        alt="FIRC Icon"
        className="firc-settings-banner-icon"
        width="24"
        height="24"
      />
      <div>
        Download monthly <b>e-FIRC</b> directly from the dashboard now!
      </div>
      <Link to="/profile/view_firc" className="link" onClick={clickHandler}>
        <u>
          <b>View FIRC</b>
        </u>
      </Link>
    </div>
  );
};

export default Firc;
