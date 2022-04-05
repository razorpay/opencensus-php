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

  function sendToSegment(objectName, actionName, properties, toCleverTap = false) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'list payment page',
      toCleverTap,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
      },
    });
  }

  return {
    searchCount: (event) => {
      sendToLumberjack('search.count', { value: event.target.value });
      sendToSegment('search with count', 'input', { value: event.target.value });
    },
    searchStatus: (event) => {
      sendToLumberjack('search.status', { value: event.target.value || 'all' });
      sendToSegment('search with status', 'click', { value: event.target.value || 'all' });
    },
    searchTitle: (event) => {
      sendToLumberjack('search.title', { value: event.target.value });
      sendToSegment('search with title', 'input', { value: event.target.value });
    },
    search: (params) => {
      sendToLumberjack('search', params);
      sendToSegment('search ', 'click', params);
    },
    searchClear: () => {
      sendToLumberjack('search.clear');
      sendToSegment('clear search filters', 'click');
    },
    createPaymentPage: () => {
      sendToLumberjack('create.click_create');
      sendToSegment('create page', 'clicked', {}, true);
      triggerHotjarRecording('PP_Creation');
    },
    paginate: (type, data) => {
      sendToLumberjack(`browse.${type}`, data);
      sendToSegment(`browse ${type}`, 'click', data);
    },
    takeTour: () => {
      sendToLumberjack('list.take_tour');
      sendToSegment('take tour', 'clicked');
    },
    viewDoc: () => {
      sendToLumberjack('list.view_documentation');
      sendToSegment('view documentation', 'clicked');
    },
    copyUrl: () => {
      sendToLumberjack('list.copy_url');
      sendToSegment('copy url', 'clicked');
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },
  };
}

export default _track();
