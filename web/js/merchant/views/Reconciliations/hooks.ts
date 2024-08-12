import { useEffect } from 'react';

import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

type UseReadTrackingProps = {
  objectName: string;
  screen: string;
  properties?: object;
  readDelay?: number;
};

const useReconTracking = ({
  objectName,
  screen,
  properties = {},
  readDelay = 15_000,
}: UseReadTrackingProps) => {
  useEffect(() => {
    analyticsTrackWithUserInfo({
      objectName,
      actionName: 'view',
      screen,
      properties,
    });
    // Will trigger read success after 15 seconds if the user is still on the page
    const timerId = setTimeout(() => {
      analyticsTrackWithUserInfo({
        objectName,
        actionName: 'read success',
        screen,
        properties,
      });
    }, readDelay);
    return () => clearTimeout(timerId);
  }, []);
};

export { useReconTracking };
