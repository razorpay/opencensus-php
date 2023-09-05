import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { getUser } from 'merchant/store';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

import { getAction } from './utils';

const trackSelfServe = ({ action, page, splitz }) => {
  const user = getUser();
  const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;
  selfServeTrackInitiate({
    selfServeAction: action,
    page,
    screen: 'Transactions',
    version,
  });
};

const trackSelfServeSuccess = ({ action, page, splitz }) => {
  const user = getUser();
  const version = isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;
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
