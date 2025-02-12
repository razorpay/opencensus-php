import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

import { getAction } from './utils';

const trackSelfServe = ({ action, page }) => {
  const version = 'v2';
  selfServeTrackInitiate({
    selfServeAction: action,
    page,
    screen: 'Transactions',
    version,
  });
};

const trackSelfServeSuccess = ({ action, page }) => {
  const version = 'v2';
  selfServeTrackSuccess({
    selfServeAction: action,
    page,
    screen: 'Transactions',
    version,
  });
};

export const selfServerTrack = ({ type, actionType = 'fetch', splitz }) => {
  const { action, page } = getAction(type, actionType);
  action && trackSelfServe({ action, page, splitz });
};

export const handleChangeTrack =
  (type) =>
  ({ type: actionType, args, splitz }) => {
    const [, newValue] = args;
    newValue && selfServerTrack({ type, actionType, splitz });
  };

export const selfServeTrackResult = ({ type, actionType = 'fetch', splitz }) => {
  const { action, page } = getAction(type, actionType);
  action && trackSelfServeSuccess({ action, page, splitz });
};
