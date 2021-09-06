import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { triggerHotjarRecording } from 'common/utils/hotjar';

function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(event, data) {
    lumberjackTrack(
      window.rzpQ.paymentPages().interaction(`pp.${event}`, {
        data,
      }),
    );
  }

  function sendToSegment(objectName, actionName, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'list payment page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
    });
  }

  return {
    searchCount: () => {
      sendToLumberjack('search.count');
      sendToSegment('search with count', 'click');
    },
    searchStatus: () => {
      sendToLumberjack('search.status');
      sendToSegment('search with status', 'click');
    },
    searchTitle: () => {
      sendToLumberjack('search.title');
      sendToSegment('search with title', 'click');
    },
    searchClear: () => {
      sendToLumberjack('search.clear');
      sendToSegment('clear search filters', 'click');
    },
    createPaymentPage: () => {
      sendToLumberjack('create.click_create');
      sendToSegment('create page', 'clicked');
      triggerHotjarRecording('PP_Creation');
    },
    paginate: (type, data) => {
      sendToLumberjack(`browse.${type}`, data);
      sendToSegment(`browse ${type}`, 'click', data);
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
