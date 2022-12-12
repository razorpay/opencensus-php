import React, { useEffect } from 'react';
import Button from 'common/new-ui/Button';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function JoinedWaitlistButton() {
  useEffect(() => {
    analyticsTrack({
      objectName: 'Affordability Widget Early Access Joined',
      actionName: 'appear',
      screen: 'Affordability Widget',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  }, []);

  return (
    <Button className="joined-button">
      Joined the waitlist
      <i className="i i-tick" />
    </Button>
  );
}
export default JoinedWaitlistButton;
