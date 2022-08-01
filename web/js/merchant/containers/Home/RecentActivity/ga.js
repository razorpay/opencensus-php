// eslint-disable-next-line import/no-named-as-default
import track, { trackGoToLinks } from 'merchant/containers/Home/ga';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

const selfServeAction = {
  payments: 'Payment',
  settlements: 'Settlement',
  refunds: 'Refund',
};

export const trackTabClick = (tabName, sectionTitle) => {
  track({
    eventAction: `Click - ${sectionTitle} Tab`,
    eventLabel: tabName,
  });
};

export const trackEntityClick = (tabName, sectionTitle) => {
  track({
    eventAction: `Open Details - ${tabName}`,
    eventLabel: `From ${sectionTitle}`,
  });
};

export const selfServeTracking = (tabName) => {
  selfServeTrackInitiate({
    selfServeAction: `${selfServeAction[tabName] || tabName} Details Fetched`,
    page: 'Home',
    screen: 'Home',
  });
};

export { trackGoToLinks };
