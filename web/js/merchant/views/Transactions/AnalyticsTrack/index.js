import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { getAction } from './utils';

const trackSelfServe = ({ action, page }) =>
  selfServeTrackInitiate({
    selfServeAction: action,
    page,
    screen: 'Transactions',
  });

export const selfServerTrack = ({ type, actionType = 'fetch' }) => {
  const { action, page } = getAction(type, actionType);
  action && trackSelfServe({ action, page });
};

export const handleChangeTrack = (type) => ({ type: actionType, args }) => {
  const [, newValue] = args;
  newValue && selfServerTrack({ type, actionType });
};
