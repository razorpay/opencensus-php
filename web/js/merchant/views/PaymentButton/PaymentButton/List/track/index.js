import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function _track() {
  let lumberjackTrack = () => {};

  function sendToLumberjack(event, options) {
    lumberjackTrack(window.rzpQ.paymentButtons().interaction(`button.${event}`, options));
  }

  function sendToSegment(objectName, actionName, properties) {
    analyticsTrack({
      objectName,
      actionName,
      screen: 'list payment buttons',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...properties,
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
      sendToLumberjack('create.enter');
      sendToSegment('create payment button', 'click');
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
  };
}

export default _track();
