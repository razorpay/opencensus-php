import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { getAction } from './utils';

const trackSelfServe = ({ action, page }) =>
  selfServeTrackInitiate({
    selfServeAction: action,
    page,
    screen: 'Transactions',
  });

const trackSelfServeSuccess = ({ action, page }) =>
  selfServeTrackSuccess({
    selfServeAction: action,
    page,
    screen: 'Transactions',
  });

export const selfServerTrack = ({ type, actionType = 'fetch' }) => {
  const { action, page } = getAction(type, actionType);
  action && trackSelfServe({ action, page });
};

export const handleChangeTrack =
  (type) =>
  ({ type: actionType, args }) => {
    const [, newValue] = args;
    newValue && selfServerTrack({ type, actionType });
  };

export const selfServeTrackResult = ({ type, actionType = 'fetch' }) => {
  const { action, page } = getAction(type, actionType);
  action && trackSelfServeSuccess({ action, page });
};
