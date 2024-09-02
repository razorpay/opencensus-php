import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(event, options) {
    lumberjackTrack(window.rzpQ.paymentButtons().interaction(`button.${event}`, options));
  }

  const default_screen = 'list payment buttons';

  function sendToSegment(
    objectName,
    actionName,
    properties,
    screen = default_screen,
    section,
    toCleverTap = false,
  ) {
    analyticsTrack({
      objectName,
      actionName,
      screen,
      toCleverTap,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
        section,
      },
    });
  }

  return {
    getCode: (button_id) => {
      sendToLumberjack('listing.review.get_code', { button_id });
      sendToSegment('get code modal', 'open', { button_id });
    },
    codeCopy: (button_id) => {
      sendToLumberjack('listing.review.copy_code', { button_id });
      sendToSegment('get code modal', 'copy code', { button_id });
    },
    openDocs: (button_id) => {
      sendToLumberjack('listing.review.open_docs', { button_id });
      sendToSegment('get code modal docs', 'click', { button_id });
    },
    getCodeModalClosed: (button_id) => {
      sendToLumberjack('listing.review.close_code', { button_id });
      sendToSegment('get code modal', 'close', { button_id });
    },
    createEnter: () => {
      selfServeTrackInitiate({
        selfServeAction: 'Create Payment Button',
        page: 'Edit Payment Button',
        screen: 'Button Create',
      });
      sendToLumberjack('create.enter');
      sendToSegment('create payment button', 'click', {}, default_screen, default_screen, true);
    },
    paginate: (params, type) => {
      sendToLumberjack(`browse.${type}`, {
        page: params.skip % params.count,
      });
      sendToSegment(`browse ${type}`, 'click', {
        page: params.skip % params.count,
      });
    },
    errorCloseClick: (error) => {
      sendToLumberjack('listing.search.error', { error });
      sendToSegment('error', 'search', { error });
    },
    searchClear: () => {
      sendToLumberjack('search.clear');
      sendToSegment('search clear', 'click');
    },
    searchTitle: () => {
      sendToLumberjack('search.title');
      sendToSegment('search title', 'input');
    },
    searchCount: () => {
      sendToLumberjack('search.count');
      sendToSegment('search count', 'input');
    },

    init(_lumberjackTrack) {
      lumberjackTrack = _lumberjackTrack;
    },

    tourPageRendered: () => {
      sendToSegment('Tour page', 'rendered', {}, 'Payment Button Tour', 'Payment Button Tour');
    },

    readMoreClickedOnTourPage: () => {
      sendToSegment('read more', 'clicked', {}, 'Payment Button Tour', 'Payment Button Tour');
    },

    getStartedClickedOnTourPage: () => {
      sendToSegment('get started', 'clicked', {}, 'Payment Button Tour', 'Payment Button Tour');
    },

    skipClickOnTourPage: () => {
      sendToSegment('skip section', 'clicked', {}, 'Payment Button Tour', 'Payment Button Tour');
    },
  };
}

export default _track();
