import React, { useEffect } from 'react';
import Button from 'common/new-ui/Button';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function JoinedWaitlistButton(props) {
  const { platform } = props;

  useEffect(() => {
    analyticsTrack({
      objectName: `Affordability Widget ${platform} Plugin Early Access Joined`,
      actionName: 'appear',
      screen: `Affordability Widget ${platform} Setup`,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });
  }, []);

  return (
    <Button className="Button--primary btn-lg btn-feedback" disabled={true}>
      Joined the waitlist
    </Button>
  );
}
export default JoinedWaitlistButton;
